<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Console\Commands;

use Illuminate\Console\Command;
use Nas11ai\SchemaGuard\Contracts\DriftDetector;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;

class SchemaGuardCheckCommand extends Command
{
  protected $signature = 'schema-guard:check
                            {--save : Save current schema as baseline snapshot}
                            {--schema= : Specific schema to check}';

  protected $description = 'Check for schema drift between database and migrations';

  public function handle(DriftDetector $detector, SchemaRepository $repository): int
  {
    if (!config('schema-guard.enabled')) {
      $this->warn('SchemaGuard is disabled. Enable it in config/schema-guard.php');

      return self::FAILURE;
    }

    $this->info('🔍 Checking for schema drift...');
    $this->newLine();

    // If --save flag is provided, save snapshot and exit
    if ($this->option('save')) {
      return $this->saveSnapshot($detector, $repository);
    }

    // Detect drift
    $drift = $detector->detectDrift();

    if (!$drift['has_drift']) {
      $this->info('✅ No schema drift detected!');
      $this->info('Your database schema matches the expected state.');

      return self::SUCCESS;
    }

    // Display drift information
    $this->displayDrift($drift);

    return self::FAILURE;
  }

  /**
   * Save a new snapshot.
   */
  private function saveSnapshot(DriftDetector $detector, SchemaRepository $repository): int
  {
    $this->info('💾 Creating schema snapshot...');

    $snapshot = $detector->createSnapshot();
    $saved = $repository->saveSnapshot($snapshot);

    if ($saved) {
      $this->info('✅ Schema snapshot saved successfully!');
      $this->info("   Tables: {$snapshot->metadata['table_count']}");
      $this->info("   Connection: {$snapshot->connection}");

      return self::SUCCESS;
    }

    $this->error('❌ Failed to save schema snapshot.');

    return self::FAILURE;
  }

  /**
   * Display drift information.
   *
   * @param array<string, mixed> $drift
   */
  private function displayDrift(array $drift): void
  {
    $this->error('❌ Schema drift detected!');
    $this->newLine();

    $summary = $drift['summary'] ?? [];

    if ($summary['tables_added'] > 0) {
      $this->warn("📊 Tables Added: {$summary['tables_added']}");
      foreach ($drift['added_tables'] as $table) {
        $this->line("   • {$table}");
      }
      $this->newLine();
    }

    if ($summary['tables_removed'] > 0) {
      $this->error("📊 Tables Removed: {$summary['tables_removed']}");
      foreach ($drift['removed_tables'] as $table) {
        $this->line("   • {$table}");
      }
      $this->newLine();
    }

    if ($summary['tables_modified'] > 0) {
      $this->warn("📊 Tables Modified: {$summary['tables_modified']}");
      foreach ($drift['modified_tables'] as $tableName => $changes) {
        $this->line("   • {$tableName}");

        if (isset($changes['added_columns'])) {
          $this->line("      Added columns: " . implode(', ', $changes['added_columns']));
        }

        if (isset($changes['removed_columns'])) {
          $this->line("      Removed columns: " . implode(', ', $changes['removed_columns']));
        }

        if (isset($changes['modified_columns'])) {
          $this->line("      Modified columns: " . implode(', ', array_keys($changes['modified_columns'])));
        }
      }
      $this->newLine();
    }

    $this->comment('💡 Tip: Run migrations to sync your database, or update your snapshot:');
    $this->comment('   php artisan migrate');
    $this->comment('   php artisan schema-guard:check --save');
  }
}