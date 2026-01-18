<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

class IndexDefinition
{
  /**
   * @param array<string> $columns
   */
  public function __construct(
    public readonly string $name,
    public readonly array $columns,
    public readonly bool $unique = false,
    public readonly bool $primary = false,
    public readonly ?string $type = null,
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
      unique: $data['unique'] ?? false,
      primary: $data['primary'] ?? false,
      type: $data['type'] ?? null,
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
      'unique' => $this->unique,
      'primary' => $this->primary,
      'type' => $this->type,
    ];
  }

  /**
   * Check if this index definition equals another.
   */
  public function equals(self $other): bool
  {
    return $this->toArray() === $other->toArray();
  }

  /**
   * Get a hash of this index definition for comparison.
   */
  public function getHash(): string
  {
    return md5(json_encode($this->toArray()));
  }
}