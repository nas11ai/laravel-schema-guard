<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\ValueObjects;

enum DangerLevel: string
{
  case SAFE = 'safe';
  case LOW = 'low';
  case MEDIUM = 'medium';
  case HIGH = 'high';
  case CRITICAL = 'critical';

  /**
   * Get the color representation for CLI output.
   */
  public function getColor(): string
  {
    return match ($this) {
      self::SAFE => 'green',
      self::LOW => 'blue',
      self::MEDIUM => 'yellow',
      self::HIGH => 'red',
      self::CRITICAL => 'red',
    };
  }

  /**
   * Get the description of the danger level.
   */
  public function getDescription(): string
  {
    return match ($this) {
      self::SAFE => 'No dangerous operations detected',
      self::LOW => 'Minor schema changes that are generally safe',
      self::MEDIUM => 'Schema modifications that may affect performance',
      self::HIGH => 'Potentially destructive operations detected',
      self::CRITICAL => 'Data loss operations detected - proceed with extreme caution',
    };
  }

  /**
   * Check if confirmation is required for this danger level.
   */
  public function requiresConfirmation(): bool
  {
    return in_array($this, [self::HIGH, self::CRITICAL]);
  }

  /**
   * Get the priority value (higher = more dangerous).
   */
  public function getPriority(): int
  {
    return match ($this) {
      self::SAFE => 0,
      self::LOW => 1,
      self::MEDIUM => 2,
      self::HIGH => 3,
      self::CRITICAL => 4,
    };
  }

  /**
   * Compare danger levels and return the higher one.
   */
  public function max(self $other): self
  {
    return $this->getPriority() >= $other->getPriority() ? $this : $other;
  }
}