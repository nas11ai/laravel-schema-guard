<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;
use Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType;

class MigrationOperation
{
  public function __construct(
    public readonly OperationType $type,
    public readonly string $tableName,
    public readonly ?string $columnName = null,
    public readonly ?string $indexName = null,
    public readonly int $lineNumber = 0,
    public readonly ?string $rawCode = null,
  ) {
  }

  /**
   * Get the danger level of this operation.
   */
  public function getDangerLevel(): DangerLevel
  {
    return $this->type->getDangerLevel();
  }

  /**
   * Check if this operation is destructive.
   */
  public function isDestructive(): bool
  {
    return $this->type->isDestructive();
  }

  /**
   * Check if this operation requires backup.
   */
  public function requiresBackup(): bool
  {
    return $this->type->requiresBackup();
  }

  /**
   * Get the warning message for this operation.
   */
  public function getWarning(): ?string
  {
    return $this->type->getWarning();
  }

  /**
   * Get a human-readable description of this operation.
   */
  public function getDescription(): string
  {
    $base = $this->type->getDescription();
    $target = $this->columnName ?? $this->indexName ?? '';

    if ($target) {
      return "{$base}: {$this->tableName}.{$target}";
    }

    return "{$base}: {$this->tableName}";
  }

  /**
   * Convert to array representation.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return [
      'type' => $this->type->value,
      'table' => $this->tableName,
      'column' => $this->columnName,
      'index' => $this->indexName,
      'line_number' => $this->lineNumber,
      'danger_level' => $this->getDangerLevel()->value,
      'is_destructive' => $this->isDestructive(),
      'requires_backup' => $this->requiresBackup(),
      'description' => $this->getDescription(),
      'warning' => $this->getWarning(),
    ];
  }
}