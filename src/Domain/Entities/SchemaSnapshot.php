<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

use Carbon\CarbonImmutable;

class SchemaSnapshot
{
  /**
   * @param array<TableDefinition> $tables
   * @param array<string, mixed> $metadata
   */
  public function __construct(
    public readonly CarbonImmutable $createdAt,
    public readonly string $connection,
    public readonly array $tables,
    public readonly array $metadata = [],
  ) {
  }

  /**
   * Create a new snapshot with current timestamp.
   *
   * @param array<TableDefinition> $tables
   * @param array<string, mixed> $metadata
   */
  public static function create(string $connection, array $tables, array $metadata = []): self
  {
    return new self(
      createdAt: CarbonImmutable::now(),
      connection: $connection,
      tables: $tables,
      metadata: $metadata,
    );
  }

  /**
   * Create from array representation.
   *
   * @param array<string, mixed> $data
   */
  public static function fromArray(array $data): self
  {
    $tables = array_map(
      fn(array $table) => TableDefinition::fromArray($table),
      $data['tables'] ?? []
    );

    return new self(
      createdAt: CarbonImmutable::parse($data['created_at']),
      connection: $data['connection'] ?? 'mysql',
      tables: $tables,
      metadata: $data['metadata'] ?? [],
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
      'created_at' => $this->createdAt->toIso8601String(),
      'connection' => $this->connection,
      'tables' => array_map(fn(TableDefinition $table) => $table->toArray(), $this->tables),
      'metadata' => $this->metadata,
    ];
  }

  /**
   * Get a table by name.
   */
  public function getTable(string $name): ?TableDefinition
  {
    foreach ($this->tables as $table) {
      if ($table->name === $name) {
        return $table;
      }
    }

    return null;
  }

  /**
   * Check if snapshot has a table.
   */
  public function hasTable(string $name): bool
  {
    return $this->getTable($name) !== null;
  }

  /**
   * Get all table names.
   *
   * @return array<string>
   */
  public function getTableNames(): array
  {
    return array_map(fn(TableDefinition $table) => $table->name, $this->tables);
  }

  /**
   * Convert to JSON string.
   */
  public function toJson(): string
  {
    return json_encode($this->toArray(), JSON_PRETTY_PRINT);
  }

  /**
   * Create from JSON string.
   */
  public static function fromJson(string $json): self
  {
    $data = json_decode($json, true);

    return self::fromArray($data);
  }

  /**
   * Get a hash of this snapshot for comparison.
   */
  public function getHash(): string
  {
    return md5(json_encode([
      'connection' => $this->connection,
      'tables' => array_map(fn(TableDefinition $table) => $table->getHash(), $this->tables),
    ]));
  }
}