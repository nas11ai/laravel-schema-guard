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

    if (!isset($drift['has_drift']) || !$drift['has_drift']) {
      // Check if there's a message about no baseline
      $message = $drift['message'] ?? null;
      if (is_string($message) && str_contains($message, 'No baseline')) {
        $this->info($message);
        return self::SUCCESS;
      }

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
      $tableCount = $snapshot->metadata['table_count'] ?? 0;

      if (!is_int($tableCount)) {
        assert(is_numeric($tableCount));
        $tableCount = (int) $tableCount;
      }

      $this->info('✅ Schema snapshot saved successfully!');
      $this->info('   Tables: ' . $tableCount);
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
    assert(is_array($summary));

    $tablesAdded = is_int($summary['tables_added'] ?? 0) ? $summary['tables_added'] : 0;
    $tablesRemoved = is_int($summary['tables_removed'] ?? 0) ? $summary['tables_removed'] : 0;
    $tablesModified = is_int($summary['tables_modified'] ?? 0) ? $summary['tables_modified'] : 0;

    if ($tablesAdded > 0) {
      $this->warn("📊 Tables Added: {$tablesAdded}");
      $addedTables = $drift['added_tables'] ?? [];
      assert(is_array($addedTables));
      foreach ($addedTables as $table) {
        if (!is_string($table)) {
          assert(is_scalar($table));
          $table = (string) $table;
        }
        $this->line("   • {$table}");
      }
      $this->newLine();
    }

    if ($tablesRemoved > 0) {
      $this->error("📊 Tables Removed: {$tablesRemoved}");
      $removedTables = $drift['removed_tables'] ?? [];
      assert(is_array($removedTables));
      foreach ($removedTables as $table) {
        if (!is_string($table)) {
          assert(is_scalar($table));
          $table = (string) $table;
        }
        $this->line("   • {$table}");
      }
      $this->newLine();
    }

    if ($tablesModified > 0) {
      $this->warn("📊 Tables Modified: {$tablesModified}");
      $modifiedTables = $drift['modified_tables'] ?? [];
      assert(is_array($modifiedTables));
      foreach ($modifiedTables as $tableName => $changes) {
        assert(is_array($changes));
        $tableNameStr = is_string($tableName) ? $tableName : (string) $tableName;
        $this->line("   • {$tableNameStr}");

        if (isset($changes['added_columns']) && is_array($changes['added_columns'])) {
          $this->line("      Added columns: " . implode(', ', $changes['added_columns']));
        }

        if (isset($changes['removed_columns']) && is_array($changes['removed_columns'])) {
          $this->line("      Removed columns: " . implode(', ', $changes['removed_columns']));
        }

        if (isset($changes['modified_columns']) && is_array($changes['modified_columns'])) {
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