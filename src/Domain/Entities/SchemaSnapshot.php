<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

use Carbon\CarbonImmutable;

/**
 * @phpstan-import-type TableDefinitionArray from TableDefinition
 * @phpstan-type SchemaSnapshotArray array{
 *   created_at: string,
 *   connection: string,
 *   tables: array<array<string, mixed>>,
 *   metadata?: array<string, mixed>
 * }
 */
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
   * @param array $data
   * @phpstan-param SchemaSnapshotArray $data
   */
  public static function fromArray(array $data): self
  {
    $tables = array_map(
      function (array $table): TableDefinition {
        /** @phpstan-var TableDefinitionArray $table */
        return TableDefinition::fromArray($table);
      },
      $data['tables']
    );

    return new self(
      createdAt: CarbonImmutable::parse($data['created_at']),
      connection: $data['connection'],
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
      'tables' => array_map(
        static fn(TableDefinition $table): array => $table->toArray(),
        $this->tables
      ),
      'metadata' => $this->metadata,
    ];
  }

  public function getTable(string $name): ?TableDefinition
  {
    foreach ($this->tables as $table) {
      if ($table->name === $name) {
        return $table;
      }
    }

    return null;
  }

  public function hasTable(string $name): bool
  {
    return $this->getTable($name) !== null;
  }

  /**
   * @return array<string>
   */
  public function getTableNames(): array
  {
    return array_map(
      static fn(TableDefinition $table): string => $table->name,
      $this->tables
    );
  }

  public function toJson(): string
  {
    return json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT);
  }

  public static function fromJson(string $json): self
  {
    /** @var SchemaSnapshotArray $data */
    $data = json_decode($json, true, 512, JSON_THROW_ON_ERROR);

    return self::fromArray($data);
  }

  public function getHash(): string
  {
    $json = json_encode([
      'connection' => $this->connection,
      'tables' => array_map(
        static fn(TableDefinition $table): string => $table->getHash(),
        $this->tables
      ),
    ], JSON_THROW_ON_ERROR);

    return md5($json);
  }
}
