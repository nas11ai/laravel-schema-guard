<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Contracts;

use Illuminate\Database\Connection;

interface DatabaseAdapter
{
  /**
   * Get the database connection.
   */
  public function getConnection(): Connection;

  /**
   * Get the database driver name.
   */
  public function getDriver(): string;

  /**
   * Check if the adapter supports the given driver.
   */
  public function supports(string $driver): bool;

  /**
   * Get SQL for a dry-run of the given migration.
   *
   * @return array<string>
   */
  public function getDryRunSql(string $migrationPath): array;

  /**
   * Execute a query and return the result.
   *
   * @param array<mixed> $bindings
   * @return mixed
   */
  public function query(string $sql, array $bindings = []): mixed;
}