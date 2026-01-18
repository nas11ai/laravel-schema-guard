<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Services;

use Nas11ai\SchemaGuard\Contracts\DriftDetector;
use Nas11ai\SchemaGuard\Contracts\SchemaInspector;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;
use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;
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
    assert(is_string($connection));

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

    return (bool) ($drift['has_drift'] ?? false);
  }

  /**
   * Compare two table definitions.
   *
   * @return array<string, mixed>
   */
  private function compareTableDefinitions(TableDefinition $expected, TableDefinition $actual): array
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
    $addedTables = is_array($drift['added_tables']) ? $drift['added_tables'] : [];
    $removedTables = is_array($drift['removed_tables']) ? $drift['removed_tables'] : [];
    $modifiedTables = is_array($drift['modified_tables']) ? $drift['modified_tables'] : [];

    return [
      'tables_added' => count($addedTables),
      'tables_removed' => count($removedTables),
      'tables_modified' => count($modifiedTables),
      'total_changes' => count($addedTables) + count($removedTables) + count($modifiedTables),
    ];
  }
}