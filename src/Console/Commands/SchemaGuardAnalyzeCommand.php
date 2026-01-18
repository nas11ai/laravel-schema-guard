<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Console\Commands;

use Illuminate\Console\Command;
use Nas11ai\SchemaGuard\Contracts\MigrationAnalyzer;
use Nas11ai\SchemaGuard\Domain\Services\SafetyGuardService;
use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;

class SchemaGuardAnalyzeCommand extends Command
{
  protected $signature = 'schema-guard:analyze
                            {migration? : Specific migration file to analyze}';

  protected $description = 'Analyze migrations for dangerous operations';

  public function handle(MigrationAnalyzer $analyzer, SafetyGuardService $safetyGuard): int
  {
    if (!config('schema-guard.enabled')) {
      $this->warn('SchemaGuard is disabled. Enable it in config/schema-guard.php');

      return self::FAILURE;
    }

    $this->info('🔍 Analyzing migrations for dangerous operations...');
    $this->newLine();

    $specificMigration = $this->argument('migration');

    if ($specificMigration) {
      return $this->analyzeSingleMigration($specificMigration, $analyzer, $safetyGuard);
    }

    return $this->analyzePendingMigrations($analyzer, $safetyGuard);
  }

  /**
   * Analyze a single migration file.
   */
  private function analyzeSingleMigration(
    string $migrationPath,
    MigrationAnalyzer $analyzer,
    SafetyGuardService $safetyGuard
  ): int {
    if (!file_exists($migrationPath)) {
      $this->error("Migration file not found: {$migrationPath}");

      return self::FAILURE;
    }

    $operations = $analyzer->analyze($migrationPath);

    if (empty($operations)) {
      $this->info('✅ No dangerous operations detected in this migration.');

      return self::SUCCESS;
    }

    $validation = $safetyGuard->validateMigration($operations);

    $this->displayMigrationAnalysis(basename($migrationPath), $operations, $validation);

    return $validation['is_safe'] ? self::SUCCESS : self::FAILURE;
  }

  /**
   * Analyze all pending migrations.
   */
  private function analyzePendingMigrations(
    MigrationAnalyzer $analyzer,
    SafetyGuardService $safetyGuard
  ): int {
    $results = $analyzer->analyzePendingMigrations();

    if (empty($results)) {
      $this->info('✅ No pending migrations to analyze.');

      return self::SUCCESS;
    }

    $hasDangerousOperations = false;

    foreach ($results as $migrationPath => $operations) {
      $validation = $safetyGuard->validateMigration($operations);

      $this->displayMigrationAnalysis(basename($migrationPath), $operations, $validation);

      if (!$validation['is_safe']) {
        $hasDangerousOperations = true;
      }

      $this->newLine();
    }

    if ($hasDangerousOperations) {
      $this->newLine();
      $this->warn('⚠️  Some migrations contain dangerous operations!');
      $this->comment('💡 Tip: Review and backup your database before running these migrations.');
      $this->comment('   Use: php artisan schema-guard:dry-run to see what will be executed.');

      return self::FAILURE;
    }

    $this->info('✅ All pending migrations are safe.');

    return self::SUCCESS;
  }

  /**
   * Display analysis results for a migration.
   *
   * @param array<mixed> $operations
   * @param array<string, mixed> $validation
   */
  private function displayMigrationAnalysis(string $filename, array $operations, array $validation): void
  {
    $dangerLevel = DangerLevel::from($validation['danger_level']->value);
    $color = $dangerLevel->getColor();

    $this->line("📄 <fg={$color}>{$filename}</>");
    $this->line("   Danger Level: <fg={$color}>{$dangerLevel->value}</>");

    if (!empty($validation['warnings'])) {
      $this->newLine();
      $this->warn('   Warnings:');
      foreach ($validation['warnings'] as $warning) {
        $this->line("   ⚠️  {$warning['operation']} (Line {$warning['line']})");
        $this->line("      {$warning['message']}");
      }
    }

    if (!empty($validation['destructive_operations'])) {
      $this->newLine();
      $this->error('   Destructive Operations:');
      foreach ($validation['destructive_operations'] as $operation) {
        $this->line("   ❌ {$operation['description']} (Line {$operation['line_number']})");
      }
    }

    if ($validation['requires_backup']) {
      $this->newLine();
      $this->warn('   💾 BACKUP RECOMMENDED before running this migration!');
    }

    if ($validation['requires_confirmation']) {
      $this->newLine();
      $this->error('   ⛔ This migration requires manual confirmation to proceed.');
    }
  }
}