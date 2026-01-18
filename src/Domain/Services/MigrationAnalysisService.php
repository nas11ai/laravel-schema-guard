<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Services;

use Nas11ai\SchemaGuard\Contracts\MigrationAnalyzer;
use Nas11ai\SchemaGuard\Domain\Entities\MigrationOperation;
use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;
use Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;

class MigrationAnalysisService implements MigrationAnalyzer
{
  public function __construct(
    private readonly MigrationRepository $repository,
  ) {
  }

  /**
   * Analyze a migration file for dangerous operations.
   *
   * @return array<MigrationOperation>
   */
  public function analyze(string $migrationPath): array
  {
    $content = file_get_contents($migrationPath);
    if ($content === false) {
      return [];
    }

    $operations = [];
    $lines = explode("\n", $content);

    foreach ($lines as $lineNumber => $line) {
      $operation = $this->parseLine($line, $lineNumber + 1);
      if ($operation) {
        $operations[] = $operation;
      }
    }

    return $operations;
  }

  /**
   * Analyze pending migrations.
   *
   * @return array<string, array<MigrationOperation>>
   */
  public function analyzePendingMigrations(): array
  {
    $pendingMigrations = $this->repository->getPendingMigrations();
    $results = [];

    foreach ($pendingMigrations as $migration) {
      $operations = $this->analyze($migration);
      if (!empty($operations)) {
        $results[$migration] = $operations;
      }
    }

    return $results;
  }

  /**
   * Check if a migration contains dangerous operations.
   */
  public function hasDangerousOperations(string $migrationPath): bool
  {
    $operations = $this->analyze($migrationPath);

    foreach ($operations as $operation) {
      if ($operation->getDangerLevel()->getPriority() >= DangerLevel::HIGH->getPriority()) {
        return true;
      }
    }

    return false;
  }

  /**
   * Get the danger level of a migration.
   */
  public function getDangerLevel(string $migrationPath): string
  {
    $operations = $this->analyze($migrationPath);

    if (empty($operations)) {
      return DangerLevel::SAFE->value;
    }

    $maxDangerLevel = DangerLevel::SAFE;
    foreach ($operations as $operation) {
      $maxDangerLevel = $maxDangerLevel->max($operation->getDangerLevel());
    }

    return $maxDangerLevel->value;
  }

  /**
   * Parse a single line of migration code.
   */
  private function parseLine(string $line, int $lineNumber): ?MigrationOperation
  {
    $line = trim($line);

    // Skip comments and empty lines
    if (empty($line) || str_starts_with($line, '//') || str_starts_with($line, '#')) {
      return null;
    }

    // Pattern matching for different operations
    $patterns = [
      // Drop operations
      '/Schema::dropIfExists\([\'"](\w+)[\'"]\)/' => OperationType::DROP_TABLE_IF_EXISTS,
      '/Schema::drop\([\'"](\w+)[\'"]\)/' => OperationType::DROP_TABLE,
      '/->dropColumn\([\'"](\w+)[\'"]\)/' => OperationType::DROP_COLUMN,
      '/->dropColumn\(\[(.*?)\]\)/' => OperationType::DROP_COLUMN,
      '/->dropIndex\([\'"](\w+)[\'"]\)/' => OperationType::DROP_INDEX,
      '/->dropForeign\([\'"](\w+)[\'"]\)/' => OperationType::DROP_FOREIGN_KEY,
      '/->dropPrimary\(\)/' => OperationType::DROP_PRIMARY,
      '/->dropUnique\([\'"](\w+)[\'"]\)/' => OperationType::DROP_UNIQUE,

      // Create operations
      '/Schema::create\([\'"](\w+)[\'"]\)/' => OperationType::CREATE_TABLE,

      // Alter operations
      '/->change\(\)/' => OperationType::CHANGE_COLUMN,
      '/->renameColumn\([\'"](\w+)[\'"],\s*[\'"](\w+)[\'"]\)/' => OperationType::RENAME_COLUMN,
      '/Schema::rename\([\'"](\w+)[\'"],\s*[\'"](\w+)[\'"]\)/' => OperationType::RENAME_TABLE,

      // Add operations
      '/->foreign\([\'"](\w+)[\'"]\)/' => OperationType::ADD_FOREIGN_KEY,
      '/->index\([\'"](\w+)[\'"]\)/' => OperationType::ADD_INDEX,
      '/->unique\([\'"](\w+)[\'"]\)/' => OperationType::ADD_UNIQUE,
      '/->primary\([\'"](\w+)[\'"]\)/' => OperationType::ADD_PRIMARY,
    ];

    foreach ($patterns as $pattern => $operationType) {
      if (preg_match($pattern, $line, $matches)) {
        return $this->createOperation($operationType, $matches, $lineNumber, $line);
      }
    }

    return null;
  }

  /**
   * Create a MigrationOperation from matched pattern.
   *
   * @param array<string> $matches
   */
  private function createOperation(
    OperationType $type,
    array $matches,
    int $lineNumber,
    string $rawCode
  ): MigrationOperation {
    $tableName = $matches[1] ?? 'unknown';
    $columnOrIndexName = $matches[2] ?? null;

    // For column operations, try to extract table name from context
    if (
      in_array($type, [
        OperationType::DROP_COLUMN,
        OperationType::CHANGE_COLUMN,
        OperationType::RENAME_COLUMN,
      ])
    ) {
      return new MigrationOperation(
        type: $type,
        tableName: $tableName,
        columnName: $columnOrIndexName ?? $tableName,
        lineNumber: $lineNumber,
        rawCode: $rawCode,
      );
    }

    // For index operations
    if (
      in_array($type, [
        OperationType::DROP_INDEX,
        OperationType::ADD_INDEX,
        OperationType::DROP_UNIQUE,
        OperationType::ADD_UNIQUE,
      ])
    ) {
      return new MigrationOperation(
        type: $type,
        tableName: $tableName,
        indexName: $columnOrIndexName ?? $tableName,
        lineNumber: $lineNumber,
        rawCode: $rawCode,
      );
    }

    return new MigrationOperation(
      type: $type,
      tableName: $tableName,
      lineNumber: $lineNumber,
      rawCode: $rawCode,
    );
  }
}