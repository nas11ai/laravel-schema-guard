<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Contracts;

use Nas11ai\SchemaGuard\Domain\Entities\MigrationOperation;

interface MigrationAnalyzer
{
  /**
   * Analyze a migration file for dangerous operations.
   *
   * @return array<MigrationOperation>
   */
  public function analyze(string $migrationPath): array;

  /**
   * Analyze pending migrations.
   *
   * @return array<string, array<MigrationOperation>>
   */
  public function analyzePendingMigrations(): array;

  /**
   * Check if a migration contains dangerous operations.
   */
  public function hasDangerousOperations(string $migrationPath): bool;

  /**
   * Get the danger level of a migration.
   */
  public function getDangerLevel(string $migrationPath): string;
}