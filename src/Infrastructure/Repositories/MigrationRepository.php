<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Infrastructure\Repositories;

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

class MigrationRepository
{
  public function __construct(
    private readonly Migrator $migrator,
  ) {
  }

  /**
   * Get all pending migrations.
   *
   * @return array<string>
   */
  public function getPendingMigrations(): array
  {
    $ran = $this->getRanMigrations();
    $migrations = $this->getAllMigrations();

    return array_values(array_diff($migrations, $ran));
  }

  /**
   * Get all ran migrations.
   *
   * @return array<string>
   */
  public function getRanMigrations(): array
  {
    if (!$this->migrationTableExists()) {
      return [];
    }

    /** @var array<string> $migrations */
    $migrations = DB::table($this->getMigrationTable())
      ->orderBy('batch')
      ->pluck('migration')
      // @phpstan-ignore-next-line
      ->map(fn($value): string => (string) $value)
      ->toArray();

    return $migrations;
  }

  /**
   * Get all migration files.
   *
   * @return array<string>
   */
  public function getAllMigrations(): array
  {
    $paths = $this->migrator->paths();
    $migrations = [];

    foreach ($paths as $path) {
      if (!File::exists($path)) {
        continue;
      }

      $files = File::files($path);
      foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
          $migrations[] = $file->getRealPath();
        }
      }
    }

    return $migrations;
  }

  /**
   * Get migration file path by name.
   */
  public function getMigrationPath(string $migrationName): ?string
  {
    $migrations = $this->getAllMigrations();

    foreach ($migrations as $migration) {
      if (str_contains($migration, $migrationName)) {
        return $migration;
      }
    }

    return null;
  }

  /**
   * Get the last batch number.
   */
  public function getLastBatchNumber(): int
  {
    if (!$this->migrationTableExists()) {
      return 0;
    }

    $batch = DB::table($this->getMigrationTable())->max('batch');

    return is_numeric($batch) ? (int) $batch : 0;
  }

  /**
   * Get migrations from a specific batch.
   *
   * @return array<string>
   */
  public function getMigrationsFromBatch(int $batch): array
  {
    if (!$this->migrationTableExists()) {
      return [];
    }

    /** @var array<string> $migrations */
    $migrations = DB::table($this->getMigrationTable())
      ->where('batch', $batch)
      ->pluck('migration')
      // @phpstan-ignore-next-line
      ->map(fn($value): string => (string) $value)
      ->toArray();

    return $migrations;
  }

  /**
   * Check if migration table exists.
   */
  private function migrationTableExists(): bool
  {
    $table = $this->getMigrationTable();

    return DB::getSchemaBuilder()->hasTable($table);
  }

  /**
   * Get the migration table name.
   */
  private function getMigrationTable(): string
  {
    $configValue = config('database.migrations', 'migrations');
    if (!is_string($configValue)) {
      $configValue = 'migrations';
    }

    return $configValue;
  }
}