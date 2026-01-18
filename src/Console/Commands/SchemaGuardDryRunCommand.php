<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Console\Commands;

use Illuminate\Console\Command;
use Nas11ai\SchemaGuard\Contracts\DatabaseAdapter;
use Nas11ai\SchemaGuard\Contracts\MigrationAnalyzer;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;

class SchemaGuardDryRunCommand extends Command
{
  protected $signature = 'schema-guard:dry-run
                            {migration? : Specific migration file to dry-run}
                            {--show-sql : Show the SQL that would be executed}';

  protected $description = 'Simulate migrations without executing them';

  public function handle(
    DatabaseAdapter $adapter,
    MigrationRepository $migrationRepo,
    MigrationAnalyzer $analyzer
  ): int {
    if (!config('schema-guard.enabled')) {
      $this->warn('SchemaGuard is disabled. Enable it in config/schema-guard.php');

      return self::FAILURE;
    }

    $this->info('🧪 Dry-run mode: Simulating migrations...');
    $this->newLine();

    $specificMigration = $this->argument('migration');

    if ($specificMigration) {
      return $this->dryRunSingleMigration($specificMigration, $adapter, $analyzer);
    }

    return $this->dryRunPendingMigrations($adapter, $migrationRepo, $analyzer);
  }

  /**
   * Dry-run a single migration.
   */
  private function dryRunSingleMigration(
    string $migrationPath,
    DatabaseAdapter $adapter,
    MigrationAnalyzer $analyzer
  ): int {
    if (!file_exists($migrationPath)) {
      $this->error("Migration file not found: {$migrationPath}");

      return self::FAILURE;
    }

    $this->info("📄 Analyzing: " . basename($migrationPath));
    $this->newLine();

    // Analyze for dangerous operations first
    $operations = $analyzer->analyze($migrationPath);

    if (!empty($operations)) {
      $this->warn('⚠️  Dangerous operations detected:');
      foreach ($operations as $operation) {
        $this->line("   • {$operation->getDescription()} (Line {$operation->lineNumber})");
      }
      $this->newLine();
    }

    // Get the SQL that would be executed
    if ($this->option('show-sql') || config('schema-guard.dry_run.show_sql')) {
      $this->info('🔍 SQL that would be executed:');
      $this->newLine();

      $sql = $adapter->getDryRunSql($migrationPath);

      foreach ($sql as $query) {
        $this->line("   {$query}");
      }

      $this->newLine();
    }

    $this->info('✅ Dry-run completed successfully!');
    $this->comment('💡 No changes were made to your database.');

    return self::SUCCESS;
  }

  /**
   * Dry-run all pending migrations.
   */
  private function dryRunPendingMigrations(
    DatabaseAdapter $adapter,
    MigrationRepository $migrationRepo,
    MigrationAnalyzer $analyzer
  ): int {
    $pendingMigrations = $migrationRepo->getPendingMigrations();

    if (empty($pendingMigrations)) {
      $this->info('✅ No pending migrations to dry-run.');

      return self::SUCCESS;
    }

    $this->info('Found ' . count($pendingMigrations) . ' pending migration(s)');
    $this->newLine();

    foreach ($pendingMigrations as $migrationPath) {
      $this->info("📄 " . basename($migrationPath));

      // Analyze for dangerous operations
      $operations = $analyzer->analyze($migrationPath);

      if (!empty($operations)) {
        $this->warn('   ⚠️  Dangerous operations:');
        foreach ($operations as $operation) {
          $color = $operation->getDangerLevel()->getColor();
          $this->line("      <fg={$color}>• {$operation->getDescription()}</>");
        }
      } else {
        $this->line('   ✅ Safe');
      }

      // Show SQL if requested
      if ($this->option('show-sql') || config('schema-guard.dry_run.show_sql')) {
        $this->newLine();
        $this->line('   SQL:');

        $sql = $adapter->getDryRunSql($migrationPath);

        foreach ($sql as $query) {
          $this->line("      {$query}");
        }
      }

      $this->newLine();
    }

    $this->info('✅ Dry-run completed for all pending migrations!');
    $this->comment('💡 No changes were made to your database.');

    return self::SUCCESS;
  }
}