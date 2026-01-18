<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Infrastructure\Repositories;

use Illuminate\Support\Facades\File;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;

class SchemaRepository
{
  private string $snapshotPath;

  public function __construct()
  {
    $this->snapshotPath = config('schema-guard.drift_detection.snapshot_path');
    $this->ensureDirectoryExists();
  }

  /**
   * Save a schema snapshot.
   */
  public function saveSnapshot(SchemaSnapshot $snapshot): bool
  {
    $filename = $this->generateFilename($snapshot);
    $filepath = $this->snapshotPath . '/' . $filename;

    return File::put($filepath, $snapshot->toJson()) !== false;
  }

  /**
   * Get the latest schema snapshot.
   */
  public function getLatestSnapshot(): ?SchemaSnapshot
  {
    $files = $this->getSnapshotFiles();

    if (empty($files)) {
      return null;
    }

    // Sort by modified time, newest first
    usort($files, function ($a, $b) {
      return filemtime($b) <=> filemtime($a);
    });

    $content = File::get($files[0]);

    return SchemaSnapshot::fromJson($content);
  }

  /**
   * Get all snapshots.
   *
   * @return array<SchemaSnapshot>
   */
  public function getAllSnapshots(): array
  {
    $files = $this->getSnapshotFiles();
    $snapshots = [];

    foreach ($files as $file) {
      $content = File::get($file);
      $snapshots[] = SchemaSnapshot::fromJson($content);
    }

    // Sort by created_at, newest first
    usort($snapshots, function ($a, $b) {
      return $b->createdAt->getTimestamp() <=> $a->createdAt->getTimestamp();
    });

    return $snapshots;
  }

  /**
   * Get a specific snapshot by filename.
   */
  public function getSnapshot(string $filename): ?SchemaSnapshot
  {
    $filepath = $this->snapshotPath . '/' . $filename;

    if (!File::exists($filepath)) {
      return null;
    }

    $content = File::get($filepath);

    return SchemaSnapshot::fromJson($content);
  }

  /**
   * Delete old snapshots, keeping only the specified number.
   */
  public function pruneSnapshots(int $keep = 10): int
  {
    $files = $this->getSnapshotFiles();

    if (count($files) <= $keep) {
      return 0;
    }

    // Sort by modified time, oldest first
    usort($files, function ($a, $b) {
      return filemtime($a) <=> filemtime($b);
    });

    $toDelete = array_slice($files, 0, count($files) - $keep);
    $deleted = 0;

    foreach ($toDelete as $file) {
      if (File::delete($file)) {
        $deleted++;
      }
    }

    return $deleted;
  }

  /**
   * Get all snapshot files.
   *
   * @return array<string>
   */
  private function getSnapshotFiles(): array
  {
    if (!File::exists($this->snapshotPath)) {
      return [];
    }

    return File::glob($this->snapshotPath . '/*.json');
  }

  /**
   * Generate a filename for a snapshot.
   */
  private function generateFilename(SchemaSnapshot $snapshot): string
  {
    $timestamp = $snapshot->createdAt->format('Y-m-d_His');
    $connection = $snapshot->connection;

    return "schema_snapshot_{$connection}_{$timestamp}.json";
  }

  /**
   * Ensure the snapshot directory exists.
   */
  private function ensureDirectoryExists(): void
  {
    if (!File::exists($this->snapshotPath)) {
      File::makeDirectory($this->snapshotPath, 0755, true);
    }
  }
}