<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Contracts;

use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;

interface SchemaInspector
{
  /**
   * Get all tables from the database.
   *
   * @return array<TableDefinition>
   */
  public function getTables(?string $schema = null): array;

  /**
   * Get a specific table definition.
   */
  public function getTable(string $tableName, ?string $schema = null): ?TableDefinition;

  /**
   * Get all table names from the database.
   *
   * @return array<string>
   */
  public function getTableNames(?string $schema = null): array;

  /**
   * Check if a table exists.
   */
  public function tableExists(string $tableName, ?string $schema = null): bool;

  /**
   * Get the current database schema.
   */
  public function getCurrentSchema(): string;
}