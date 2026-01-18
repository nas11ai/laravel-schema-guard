<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Infrastructure\Adapters;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Nas11ai\SchemaGuard\Contracts\DatabaseAdapter;

class PostgreSQLAdapter implements DatabaseAdapter
{
  private Connection $connection;

  public function __construct()
  {
    $connectionName = config('schema-guard.database.default');
    assert(is_string($connectionName) || is_null($connectionName));
    $this->connection = DB::connection($connectionName);
  }

  /**
   * Get the database connection.
   */
  public function getConnection(): Connection
  {
    return $this->connection;
  }

  /**
   * Get the database driver name.
   */
  public function getDriver(): string
  {
    return 'pgsql';
  }

  /**
   * Check if the adapter supports the given driver.
   */
  public function supports(string $driver): bool
  {
    return $driver === 'pgsql';
  }

  /**
   * Get SQL for a dry-run of the given migration.
   *
   * @return array<string>
   */
  public function getDryRunSql(string $migrationPath): array
  {
    // Enable query log
    DB::enableQueryLog();

    // Start a transaction that we'll rollback
    DB::beginTransaction();

    try {
      // Load and run the migration
      require_once $migrationPath;

      $migrationClass = $this->getMigrationClassName($migrationPath);
      $migration = new $migrationClass();

      if (method_exists($migration, 'up')) {
        $migration->up();
      }

      // Get the queries that were logged
      $queries = DB::getQueryLog();
      $sql = array_map(fn($query) => $this->bindQueryParams($query['query'], $query['bindings']), $queries);

      // Rollback to undo any changes
      DB::rollBack();

      return $sql;
    } catch (\Throwable $e) {
      DB::rollBack();

      return ["-- Error during dry-run: {$e->getMessage()}"];
    } finally {
      DB::disableQueryLog();
    }
  }

  /**
   * Execute a query and return the result.
   *
   * @param array<mixed> $bindings
   * @return mixed
   */
  public function query(string $sql, array $bindings = []): mixed
  {
    return $this->connection->select($sql, $bindings);
  }

  /**
   * Get the migration class name from file path.
   */
  private function getMigrationClassName(string $path): string
  {
    $filename = basename($path, '.php');

    // Remove timestamp prefix (e.g., "2024_01_01_000000_")
    $className = preg_replace('/^\d{4}_\d{2}_\d{2}_\d{6}_/', '', $filename);
    assert(is_string($className));

    // Convert to StudlyCase
    return str($className)->studly()->toString();
  }

  /**
   * Bind query parameters to SQL.
   *
   * @param array<mixed> $bindings
   */
  private function bindQueryParams(string $sql, array $bindings): string
  {
    foreach ($bindings as $binding) {
      if (is_string($binding)) {
        $value = "'{$binding}'";
      } else {
        assert(is_scalar($binding) || is_null($binding));
        $value = (string) $binding;
      }

      $replaced = preg_replace('/\?/', $value, $sql, 1);
      assert(is_string($replaced));
      $sql = $replaced;
    }

    return $sql;
  }
}