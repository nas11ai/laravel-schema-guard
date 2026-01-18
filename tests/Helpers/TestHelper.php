<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Tests\Helpers;

use Illuminate\Support\Facades\Schema;
use Nas11ai\SchemaGuard\Domain\Entities\ColumnDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\IndexDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;

class TestHelper
{
  /**
   * Create a simple table for testing.
   */
  public static function createTestTable(string $tableName = 'test_table'): void
  {
    Schema::create($tableName, function ($table) {
      $table->id();
      $table->string('name');
      $table->string('email')->unique();
      $table->timestamps();
    });
  }

  /**
   * Drop a test table.
   */
  public static function dropTestTable(string $tableName = 'test_table'): void
  {
    Schema::dropIfExists($tableName);
  }

  /**
   * Run test migrations.
   */
  public static function runTestMigrations(): void
  {
    $migrationPath = __DIR__ . '/../database/migrations';

    $migrator = app('migrator');
    $migrator->run($migrationPath);
  }

  /**
   * Rollback test migrations.
   */
  public static function rollbackTestMigrations(): void
  {
    $migrationPath = __DIR__ . '/../database/migrations';

    $migrator = app('migrator');
    $migrator->rollback($migrationPath);
  }

  /**
   * Create a mock TableDefinition.
   */
  public static function createMockTableDefinition(
    string $name = 'users',
    array $columns = []
  ): TableDefinition {
    if (empty($columns)) {
      $columns = [
        new ColumnDefinition(
          name: 'id',
          type: 'bigint',
          nullable: false,
          autoIncrement: true,
        ),
        new ColumnDefinition(
          name: 'name',
          type: 'string',
          nullable: false,
          length: 255,
        ),
        new ColumnDefinition(
          name: 'email',
          type: 'string',
          nullable: false,
          length: 255,
        ),
      ];
    }

    return new TableDefinition(
      name: $name,
      columns: $columns,
      indexes: [
        new IndexDefinition(
          name: 'users_email_unique',
          columns: ['email'],
          unique: true,
        ),
      ],
    );
  }

  /**
   * Create a mock ColumnDefinition.
   */
  public static function createMockColumnDefinition(
    string $name = 'id',
    string $type = 'bigint',
    bool $nullable = false
  ): ColumnDefinition {
    return new ColumnDefinition(
      name: $name,
      type: $type,
      nullable: $nullable,
    );
  }

  /**
   * Get test migration path.
   */
  public static function getTestMigrationPath(string $filename): string
  {
    return __DIR__ . '/../database/migrations/' . $filename;
  }

  /**
   * Clean up all test tables.
   */
  public static function cleanupTestTables(): void
  {
    $tables = ['users', 'posts', 'comments', 'test_table', 'old_logs'];

    foreach ($tables as $table) {
      Schema::dropIfExists($table);
    }
  }
}