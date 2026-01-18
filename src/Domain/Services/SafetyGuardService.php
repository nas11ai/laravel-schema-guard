<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Services;

use Nas11ai\SchemaGuard\Domain\Entities\MigrationOperation;
use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;

class SafetyGuardService
{
  /**
   * Check if an operation should be allowed based on configuration.
   */
  public function shouldAllow(MigrationOperation $operation, bool $force = false): bool
  {
    if ($force) {
      return true;
    }

    $dangerLevel = $operation->getDangerLevel();

    // In production, always require confirmation for dangerous operations
    if (app()->environment('production') && config('schema-guard.safety.strict_mode_production')) {
      return !$dangerLevel->requiresConfirmation();
    }

    return true;
  }

  /**
   * Get confirmation message for an operation.
   */
  public function getConfirmationMessage(MigrationOperation $operation): string
  {
    $dangerLevel = $operation->getDangerLevel();
    $warning = $operation->getWarning();

    $message = "⚠️  {$dangerLevel->value} operation detected:\n\n";
    $message .= "   {$operation->getDescription()}\n";

    if ($warning) {
      $message .= "\n   {$warning}\n";
    }

    $message .= "\n   Line: {$operation->lineNumber}\n";

    if ($operation->isDestructive()) {
      $message .= "\n   ❌ This operation may cause DATA LOSS!\n";
    }

    if ($operation->requiresBackup()) {
      $message .= "\n   💾 BACKUP RECOMMENDED before proceeding.\n";
    }

    return $message;
  }

  /**
   * Validate if migration is safe to run.
   *
   * @param array<MigrationOperation> $operations
   * @return array<string, mixed>
   */
  public function validateMigration(array $operations): array
  {
    $result = [
      'is_safe' => true,
      'danger_level' => DangerLevel::SAFE,
      'requires_confirmation' => false,
      'requires_backup' => false,
      'warnings' => [],
      'destructive_operations' => [],
    ];

    if (empty($operations)) {
      return $result;
    }

    $maxDangerLevel = DangerLevel::SAFE;

    foreach ($operations as $operation) {
      $dangerLevel = $operation->getDangerLevel();
      $maxDangerLevel = $maxDangerLevel->max($dangerLevel);

      if ($operation->isDestructive()) {
        $result['destructive_operations'][] = $operation->toArray();
      }

      if ($operation->requiresBackup()) {
        $result['requires_backup'] = true;
      }

      $warning = $operation->getWarning();
      if ($warning) {
        $result['warnings'][] = [
          'operation' => $operation->getDescription(),
          'message' => $warning,
          'line' => $operation->lineNumber,
        ];
      }
    }

    $result['danger_level'] = $maxDangerLevel;
    $result['is_safe'] = $maxDangerLevel->getPriority() < DangerLevel::HIGH->getPriority();
    $result['requires_confirmation'] = $maxDangerLevel->requiresConfirmation();

    return $result;
  }

  /**
   * Get environment-specific safety rules.
   *
   * @return array<string, mixed>
   */
  public function getSafetyRules(): array
  {
    $environment = app()->environment();

    return [
      'environment' => $environment,
      'strict_mode' => $environment === 'production' &&
        config('schema-guard.safety.strict_mode_production'),
      'require_confirmation' => config('schema-guard.safety.require_confirmation'),
      'dangerous_operations' => config('schema-guard.safety.dangerous_operations'),
      'destructive_operations' => config('schema-guard.safety.destructive_operations'),
    ];
  }
}