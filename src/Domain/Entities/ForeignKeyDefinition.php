<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

class ForeignKeyDefinition
{
  /**
   * @param array<string> $columns
   * @param array<string> $foreignColumns
   */
  public function __construct(
    public readonly string $name,
    public readonly array $columns,
    public readonly string $foreignTable,
    public readonly array $foreignColumns,
    public readonly ?string $onUpdate = null,
    public readonly ?string $onDelete = null,
  ) {
  }

  /**
   * Create from database metadata array.
   *
   * @param array<string, mixed> $data
   */
  public static function fromArray(array $data): self
  {
    return new self(
      name: $data['name'] ?? '',
      columns: $data['columns'] ?? [],
      foreignTable: $data['foreign_table'] ?? '',
      foreignColumns: $data['foreign_columns'] ?? [],
      onUpdate: $data['on_update'] ?? null,
      onDelete: $data['on_delete'] ?? null,
    );
  }

  /**
   * Convert to array representation.
   *
   * @return array<string, mixed>
   */
  public function toArray(): array
  {
    return [
      'name' => $this->name,
      'columns' => $this->columns,
      'foreign_table' => $this->foreignTable,
      'foreign_columns' => $this->foreignColumns,
      'on_update' => $this->onUpdate,
      'on_delete' => $this->onDelete,
    ];
  }

  /**
   * Check if this foreign key definition equals another.
   */
  public function equals(self $other): bool
  {
    return $this->toArray() === $other->toArray();
  }

  /**
   * Get a hash of this foreign key definition for comparison.
   */
  public function getHash(): string
  {
    return md5(json_encode($this->toArray()));
  }
}