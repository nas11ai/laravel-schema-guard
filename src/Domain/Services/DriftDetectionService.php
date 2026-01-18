<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Services;

use Nas11ai\SchemaGuard\Contracts\DriftDetector;
use Nas11ai\SchemaGuard\Contracts\SchemaInspector;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;

class DriftDetectionService implements DriftDetector
{
  public function __construct(
    private readonly SchemaInspector $inspector,
    private readonly SchemaRepository $repository,
  ) {
  }

  /**
   * Detect drift between current schema and expected schema.
   *
   * @return array<string, mixed>
   */
  public function detectDrift(): array
  {
    $currentSnapshot = $this->createSnapshot();
    $latestSnapshot = $this->loadLatestSnapshot();

    if (!$latestSnapshot) {
      return [
        'has_drift' => false,
        'message' => 'No baseline snapshot found. Current schema will be used as baseline.',
        'current_snapshot' => $currentSnapshot,
      ];
    }

    return $this->compareSnapshots($latestSnapshot, $currentSnapshot);
  }

  /**
   * Create a snapshot of the current schema.
   */
  public function createSnapshot(): SchemaSnapshot
  {
    $tables = $this->inspector->getTables();
    $connection = config('schema-guard.database.default', 'mysql');

    return SchemaSnapshot::create($connection, $tables, [
      'table_count' => count($tables),
    ]);
  }

  /**
   * Load the latest schema snapshot.
   */
  public function loadLatestSnapshot(): ?SchemaSnapshot
  {
    return $this->repository->getLatestSnapshot();
  }

  /**
   * Compare two schema snapshots.
   *
   * @return array<string, mixed>
   */
  public function compareSnapshots(SchemaSnapshot $expected, SchemaSnapshot $actual): array
  {
    $drift = [
      'has_drift' => false,
      'added_tables' => [],
      'removed_tables' => [],
      'modified_tables' => [],
      'summary' => [],
    ];

    $expectedTableNames = $expected->getTableNames();
    $actualTableNames = $actual->getTableNames();

    // Find added tables
    $addedTables = array_diff($actualTableNames, $expectedTableNames);
    if (!empty($addedTables)) {
      $drift['has_drift'] = true;
      $drift['added_tables'] = array_values($addedTables);
    }

    // Find removed tables
    $removedTables = array_diff($expectedTableNames, $actualTableNames);
    if (!empty($removedTables)) {
      $drift['has_drift'] = true;
      $drift['removed_tables'] = array_values($removedTables);
    }

    // Find modified tables
    $commonTables = array_intersect($expectedTableNames, $actualTableNames);
    foreach ($commonTables as $tableName) {
      $expectedTable = $expected->getTable($tableName);
      $actualTable = $actual->getTable($tableName);

      if ($expectedTable && $actualTable) {
        $tableDiff = $this->compareTableDefinitions($expectedTable, $actualTable);
        if (!empty($tableDiff)) {
          $drift['has_drift'] = true;
          $drift['modified_tables'][$tableName] = $tableDiff;
        }
      }
    }

    // Generate summary
    $drift['summary'] = $this->generateDriftSummary($drift);

    return $drift;
  }

  /**
   * Check if there is any drift.
   */
  public function hasDrift(): bool
  {
    $drift = $this->detectDrift();

    return $drift['has_drift'] ?? false;
  }

  /**
   * Compare two table definitions.
   *
   * @return array<string, mixed>
   */
  private function compareTableDefinitions($expected, $actual): array
  {
    $diff = [];

    // Compare columns
    $expectedColumns = $expected->getColumnNames();
    $actualColumns = $actual->getColumnNames();

    $addedColumns = array_diff($actualColumns, $expectedColumns);
    $removedColumns = array_diff($expectedColumns, $actualColumns);
    $modifiedColumns = [];

    $commonColumns = array_intersect($expectedColumns, $actualColumns);
    foreach ($commonColumns as $columnName) {
      $expectedCol = $expected->getColumn($columnName);
      $actualCol = $actual->getColumn($columnName);

      if ($expectedCol && $actualCol && !$expectedCol->equals($actualCol)) {
        $modifiedColumns[$columnName] = [
          'expected' => $expectedCol->toArray(),
          'actual' => $actualCol->toArray(),
        ];
      }
    }

    if (!empty($addedColumns)) {
      $diff['added_columns'] = array_values($addedColumns);
    }

    if (!empty($removedColumns)) {
      $diff['removed_columns'] = array_values($removedColumns);
    }

    if (!empty($modifiedColumns)) {
      $diff['modified_columns'] = $modifiedColumns;
    }

    return $diff;
  }

  /**
   * Generate a summary of the drift.
   *
   * @param array<string, mixed> $drift
   * @return array<string, mixed>
   */
  private function generateDriftSummary(array $drift): array
  {
    return [
      'tables_added' => count($drift['added_tables']),
      'tables_removed' => count($drift['removed_tables']),
      'tables_modified' => count($drift['modified_tables']),
      'total_changes' => count($drift['added_tables']) +
        count($drift['removed_tables']) +
        count($drift['modified_tables']),
    ];
  }
}