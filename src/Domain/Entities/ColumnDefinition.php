<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\Entities;

class ColumnDefinition
{
  public function __construct(
    public readonly string $name,
    public readonly string $type,
    public readonly bool $nullable = false,
    public readonly mixed $default = null,
    public readonly ?string $comment = null,
    public readonly ?int $length = null,
    public readonly ?int $precision = null,
    public readonly ?int $scale = null,
    public readonly bool $autoIncrement = false,
    public readonly ?string $collation = null,
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
      type: $data['type'] ?? 'string',
      nullable: $data['nullable'] ?? false,
      default: $data['default'] ?? null,
      comment: $data['comment'] ?? null,
      length: $data['length'] ?? null,
      precision: $data['precision'] ?? null,
      scale: $data['scale'] ?? null,
      autoIncrement: $data['auto_increment'] ?? false,
      collation: $data['collation'] ?? null,
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
      'type' => $this->type,
      'nullable' => $this->nullable,
      'default' => $this->default,
      'comment' => $this->comment,
      'length' => $this->length,
      'precision' => $this->precision,
      'scale' => $this->scale,
      'auto_increment' => $this->autoIncrement,
      'collation' => $this->collation,
    ];
  }

  /**
   * Check if this column definition equals another.
   */
  public function equals(self $other): bool
  {
    return $this->toArray() === $other->toArray();
  }

  /**
   * Get a hash of this column definition for comparison.
   */
  public function getHash(): string
  {
    return md5(json_encode($this->toArray()));
  }
}