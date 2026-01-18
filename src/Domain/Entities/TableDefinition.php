<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

class TableDefinition
{
  /**
   * @param array<ColumnDefinition> $columns
   * @param array<IndexDefinition> $indexes
   * @param array<ForeignKeyDefinition> $foreignKeys
   */
  public function __construct(
    public readonly string $name,
    public readonly array $columns = [],
    public readonly array $indexes = [],
    public readonly array $foreignKeys = [],
    public readonly ?string $engine = null,
    public readonly ?string $collation = null,
    public readonly ?string $comment = null,
  ) {
  }

  /**
   * Create from database metadata array.
   *
   * @param array<string, mixed> $data
   */
  public static function fromArray(array $data): self
  {
    $columns = array_map(
      fn(array $col) => ColumnDefinition::fromArray($col),
      $data['columns'] ?? []
    );

    $indexes = array_map(
      fn(array $idx) => IndexDefinition::fromArray($idx),
      $data['indexes'] ?? []
    );

    $foreignKeys = array_map(
      fn(array $fk) => ForeignKeyDefinition::fromArray($fk),
      $data['foreign_keys'] ?? []
    );

    return new self(
      name: $data['name'] ?? '',
      columns: $columns,
      indexes: $indexes,
      foreignKeys: $foreignKeys,
      engine: $data['engine'] ?? null,
      collation: $data['collation'] ?? null,
      comment: $data['comment'] ?? null,
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
      'columns' => array_map(fn(ColumnDefinition $col) => $col->toArray(), $this->columns),
      'indexes' => array_map(fn(IndexDefinition $idx) => $idx->toArray(), $this->indexes),
      'foreign_keys' => array_map(fn(ForeignKeyDefinition $fk) => $fk->toArray(), $this->foreignKeys),
      'engine' => $this->engine,
      'collation' => $this->collation,
      'comment' => $this->comment,
    ];
  }

  /**
   * Get a column by name.
   */
  public function getColumn(string $name): ?ColumnDefinition
  {
    foreach ($this->columns as $column) {
      if ($column->name === $name) {
        return $column;
      }
    }

    return null;
  }

  /**
   * Check if table has a column.
   */
  public function hasColumn(string $name): bool
  {
    return $this->getColumn($name) !== null;
  }

  /**
   * Get all column names.
   *
   * @return array<string>
   */
  public function getColumnNames(): array
  {
    return array_map(fn(ColumnDefinition $col) => $col->name, $this->columns);
  }

  /**
   * Get a hash of this table definition for comparison.
   */
  public function getHash(): string
  {
    return md5(json_encode($this->toArray()));
  }
}