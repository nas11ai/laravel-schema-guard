<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Contracts;

use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;

interface DriftDetector
{
  /**
   * Detect drift between current schema and expected schema.
   *
   * @return array<string, mixed>
   */
  public function detectDrift(): array;

  /**
   * Create a snapshot of the current schema.
   */
  public function createSnapshot(): SchemaSnapshot;

  /**
   * Load the latest schema snapshot.
   */
  public function loadLatestSnapshot(): ?SchemaSnapshot;

  /**
   * Compare two schema snapshots.
   *
   * @return array<string, mixed>
   */
  public function compareSnapshots(SchemaSnapshot $snapshot1, SchemaSnapshot $snapshot2): array;

  /**
   * Check if there is any drift.
   */
  public function hasDrift(): bool;
}