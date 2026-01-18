<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

/**
 * @phpstan-type TableDefinitionArray array{
 *   name?: string,
 *   columns?: array<int, array<string, mixed>>,
 *   indexes?: array<int, array<string, mixed>>,
 *   foreign_keys?: array<int, array<string, mixed>>,
 *   engine?: string|null,
 *   collation?: string|null,
 *   comment?: string|null
 * }
 */

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
   * @param array $data
   * @phpstan-param TableDefinitionArray $data
   */
  public static function fromArray(array $data): self
  {
    /** @var array<ColumnDefinition> $columns */
    $columns = [];
    foreach ($data['columns'] ?? [] as $col) {
      // @phpstan-ignore-next-line
      $columns[] = ColumnDefinition::fromArray($col);
    }

    /** @var array<IndexDefinition> $indexes */
    $indexes = [];
    foreach ($data['indexes'] ?? [] as $idx) {
      // @phpstan-ignore-next-line
      $indexes[] = IndexDefinition::fromArray($idx);
    }

    /** @var array<ForeignKeyDefinition> $foreignKeys */
    $foreignKeys = [];
    foreach ($data['foreign_keys'] ?? [] as $fk) {
      // @phpstan-ignore-next-line
      $foreignKeys[] = ForeignKeyDefinition::fromArray($fk);
    }

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
      'columns' => array_map(
        static fn(ColumnDefinition $col): array => $col->toArray(),
        $this->columns
      ),
      'indexes' => array_map(
        static fn(IndexDefinition $idx): array => $idx->toArray(),
        $this->indexes
      ),
      'foreign_keys' => array_map(
        static fn(ForeignKeyDefinition $fk): array => $fk->toArray(),
        $this->foreignKeys
      ),
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
    return array_map(
      static fn(ColumnDefinition $col): string => $col->name,
      $this->columns
    );
  }

  /**
   * Get a hash of this table definition for comparison.
   */
  public function getHash(): string
  {
    $json = json_encode($this->toArray(), JSON_THROW_ON_ERROR);

    return md5($json);
  }
}
