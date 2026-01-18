<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Infrastructure\Inspectors;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nas11ai\SchemaGuard\Contracts\SchemaInspector;
use Nas11ai\SchemaGuard\Domain\Entities\ColumnDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\ForeignKeyDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\IndexDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;

class PostgreSQLInspector implements SchemaInspector
{
  /**
   * Get all tables from the database.
   *
   * @return array<TableDefinition>
   */
  public function getTables(?string $schema = null): array
  {
    $tableNames = $this->getTableNames($schema);
    $tables = [];

    foreach ($tableNames as $tableName) {
      $table = $this->getTable($tableName, $schema);
      if ($table) {
        $tables[] = $table;
      }
    }

    return $tables;
  }

  /**
   * Get a specific table definition.
   */
  public function getTable(string $tableName, ?string $schema = null): ?TableDefinition
  {
    if (!$this->tableExists($tableName, $schema)) {
      return null;
    }

    $columns = $this->getColumns($tableName);
    $indexes = $this->getIndexes($tableName);
    $foreignKeys = $this->getForeignKeys($tableName);
    $tableInfo = $this->getTableInfo($tableName);

    return new TableDefinition(
      name: $tableName,
      columns: $columns,
      indexes: $indexes,
      foreignKeys: $foreignKeys,
      comment: isset($tableInfo['comment']) && is_string($tableInfo['comment']) ? $tableInfo['comment'] : null,
    );
  }

  /**
   * Get all table names from the database.
   *
   * @return array<string>
   */
  public function getTableNames(?string $schema = null): array
  {
    $excludedTables = config('schema-guard.drift_detection.excluded_tables', []);
    assert(is_array($excludedTables));

    // Use Laravel 12 Schema::getTableListing() which returns schema-qualified names by default
    $tables = Schema::getTableListing(schema: $schema ?? 'public', schemaQualified: false);

    return array_values(array_diff($tables, $excludedTables));
  }

  /**
   * Check if a table exists.
   */
  public function tableExists(string $tableName, ?string $schema = null): bool
  {
    return Schema::hasTable($tableName);
  }

  /**
   * Get the current database schema.
   */
  public function getCurrentSchema(): string
  {
    $result = DB::selectOne('SELECT current_schema() as schema');

    if (!is_object($result) || !property_exists($result, 'schema')) {
      return 'public';
    }

    return is_string($result->schema) ? $result->schema : 'public';
  }

  /**
   * Get columns for a table.
   *
   * @return array<ColumnDefinition>
   */
  private function getColumns(string $tableName): array
  {
    $connection = DB::connection();

    // @phpstan-ignore-next-line
    $columns = $connection->getDoctrineSchemaManager()->listTableColumns($tableName);
    $definitions = [];

    foreach ($columns as $column) {
      $definitions[] = new ColumnDefinition(
        name: $column->getName(),
        type: $column->getType()->getName(),
        nullable: !$column->getNotnull(),
        default: $column->getDefault(),
        comment: $column->getComment(),
        length: $column->getLength(),
        precision: $column->getPrecision(),
        scale: $column->getScale(),
        autoIncrement: $column->getAutoincrement(),
      );
    }

    return $definitions;
  }

  /**
   * Get indexes for a table.
   *
   * @return array<IndexDefinition>
   */
  private function getIndexes(string $tableName): array
  {
    $connection = DB::connection();

    // @phpstan-ignore-next-line
    $indexes = $connection->getDoctrineSchemaManager()->listTableIndexes($tableName);
    $definitions = [];

    foreach ($indexes as $index) {
      $definitions[] = new IndexDefinition(
        name: $index->getName(),
        columns: $index->getColumns(),
        unique: $index->isUnique(),
        primary: $index->isPrimary(),
      );
    }

    return $definitions;
  }

  /**
   * Get foreign keys for a table.
   *
   * @return array<ForeignKeyDefinition>
   */
  private function getForeignKeys(string $tableName): array
  {
    $connection = DB::connection();

    // @phpstan-ignore-next-line
    $foreignKeys = $connection->getDoctrineSchemaManager()->listTableForeignKeys($tableName);
    $definitions = [];

    foreach ($foreignKeys as $foreignKey) {
      $definitions[] = new ForeignKeyDefinition(
        name: $foreignKey->getName(),
        columns: $foreignKey->getLocalColumns(),
        foreignTable: $foreignKey->getForeignTableName(),
        foreignColumns: $foreignKey->getForeignColumns(),
        onUpdate: $foreignKey->onUpdate(),
        onDelete: $foreignKey->onDelete(),
      );
    }

    return $definitions;
  }

  /**
   * Get table information (comment).
   *
   * @return array<string, mixed>
   */
  private function getTableInfo(string $tableName): array
  {
    $schema = $this->getCurrentSchema();

    $result = DB::selectOne(
      "SELECT obj_description((quote_ident(?) || '.' || quote_ident(?))::regclass, 'pg_class') as comment",
      [$schema, $tableName]
    );

    if (!is_object($result)) {
      return [];
    }

    return [
      'comment' => property_exists($result, 'comment') ? $result->comment : null,
    ];
  }
}