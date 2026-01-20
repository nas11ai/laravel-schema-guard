<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\File;
use Nas11ai\SchemaGuard\Domain\Entities\ColumnDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;
use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;

beforeEach(function () {
  /** @var UnitTestCase $this */

  // Set up temporary snapshot path for testing
  $this->snapshotPath = sys_get_temp_dir() . '/schema_guard_test_' . uniqid();
  Config::set('schema-guard.drift_detection.snapshot_path', $this->snapshotPath);

  $this->schemaRepository = new SchemaRepository();
});

afterEach(function () {
  /** @var UnitTestCase $this */

  // Clean up test files
  if (File::exists($this->snapshotPath)) {
    File::deleteDirectory($this->snapshotPath);
  }
});

it('creates snapshot directory on instantiation', function () {
  /** @var UnitTestCase $this */

  expect(File::exists($this->snapshotPath))->toBeTrue()
    ->and(File::isDirectory($this->snapshotPath))->toBeTrue();
});

it('can save a schema snapshot', function () {
  /** @var UnitTestCase $this */

  $snapshot = new SchemaSnapshot(
    connection: 'mysql',
    tables: [],
    createdAt: CarbonImmutable::now()
  );

  $result = $this->schemaRepository->saveSnapshot($snapshot);

  expect($result)->toBeTrue()
    ->and(File::glob($this->snapshotPath . '/*.json'))->toHaveCount(1);
});

it('generates correct filename for snapshot', function () {
  /** @var UnitTestCase $this */

  $createdAt = CarbonImmutable::parse('2024-01-15 14:30:45');
  $snapshot = new SchemaSnapshot(
    connection: 'mysql',
    tables: [],
    createdAt: $createdAt
  );

  $this->schemaRepository->saveSnapshot($snapshot);

  $files = File::glob($this->snapshotPath . '/*.json');
  $filename = basename($files[0]);

  expect($filename)->toBe('schema_snapshot_mysql_2024-01-15_143045.json');
});

it('can retrieve the latest snapshot', function () {
  /** @var UnitTestCase $this */

  $snapshots = [
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::now()->subHours(2)
    ),
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::now()->subHour()
    ),
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::now()
    ),
  ];

  foreach ($snapshots as $i => $snapshot) {
    $this->schemaRepository->saveSnapshot($snapshot);

    $file = File::glob($this->snapshotPath . '/*.json')[$i];
    touch($file, time() + $i); // ensure increasing mtime
  }

  $latest = $this->schemaRepository->getLatestSnapshot();

  expect($latest)->not->toBeNull()
    ->and($latest->createdAt->timestamp)
    ->toBe($snapshots[2]->createdAt->timestamp);
});

it('returns null when no snapshots exist', function () {
  /** @var UnitTestCase $this */

  $latest = $this->schemaRepository->getLatestSnapshot();

  expect($latest)->toBeNull();
});

it('can get all snapshots sorted by creation time', function () {
  /** @var UnitTestCase $this */

  $snapshot1 = new SchemaSnapshot(
    connection: 'mysql',
    tables: [],
    createdAt: CarbonImmutable::parse('2024-01-15 10:00:00')
  );
  $snapshot2 = new SchemaSnapshot(
    connection: 'mysql',
    tables: [],
    createdAt: CarbonImmutable::parse('2024-01-15 12:00:00')
  );
  $snapshot3 = new SchemaSnapshot(
    connection: 'mysql',
    tables: [],
    createdAt: CarbonImmutable::parse('2024-01-15 14:00:00')
  );

  $this->schemaRepository->saveSnapshot($snapshot1);
  $this->schemaRepository->saveSnapshot($snapshot2);
  $this->schemaRepository->saveSnapshot($snapshot3);

  $snapshots = $this->schemaRepository->getAllSnapshots();

  expect($snapshots)->toHaveCount(3)
    ->and($snapshots[0]->createdAt->timestamp)->toBe($snapshot3->createdAt->timestamp)
    ->and($snapshots[1]->createdAt->timestamp)->toBe($snapshot2->createdAt->timestamp)
    ->and($snapshots[2]->createdAt->timestamp)->toBe($snapshot1->createdAt->timestamp);
});

it('returns empty array when getting all snapshots if none exist', function () {
  /** @var UnitTestCase $this */

  $snapshots = $this->schemaRepository->getAllSnapshots();

  expect($snapshots)->toBe([]);
});

it('can get a specific snapshot by filename', function () {
  /** @var UnitTestCase $this */

  $createdAt = CarbonImmutable::parse('2024-01-15 14:30:45');
  $snapshot = new SchemaSnapshot(
    connection: 'mysql',
    tables: [
      new TableDefinition(name: 'users', columns: [])
    ],
    createdAt: $createdAt
  );

  $this->schemaRepository->saveSnapshot($snapshot);

  $retrieved = $this->schemaRepository->getSnapshot('schema_snapshot_mysql_2024-01-15_143045.json');

  expect($retrieved)->not->toBeNull()
    ->and($retrieved->connection)->toBe('mysql')
    ->and($retrieved->tables)->toHaveCount(1)
    ->and($retrieved->tables[0]->name)->toBe('users');
});

it('returns null when getting non-existent snapshot', function () {
  /** @var UnitTestCase $this */

  $snapshot = $this->schemaRepository->getSnapshot('non_existent.json');

  expect($snapshot)->toBeNull();
});

it('can prune old snapshots keeping specified number', function () {
  /** @var UnitTestCase $this */

  // Create 15 snapshots
  for ($i = 0; $i < 15; $i++) {
    $snapshot = new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::now()->subHours(15 - $i)
    );

    $this->schemaRepository->saveSnapshot($snapshot);

    // Set file modified time manually (oldest → newest)
    $files = File::glob($this->snapshotPath . '/*.json');
    touch(end($files), time() - (15 - $i));
  }

  $deleted = $this->schemaRepository->pruneSnapshots(10);

  expect($deleted)->toBe(5)
    ->and(File::glob($this->snapshotPath . '/*.json'))->toHaveCount(10);
});

it('does not delete any snapshots if count is below threshold', function () {
  /** @var UnitTestCase $this */

  // Create only 5 snapshots
  for ($i = 0; $i < 5; $i++) {
    $snapshot = new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::now()->subHours(5 - $i)
    );
    $this->schemaRepository->saveSnapshot($snapshot);
  }

  $deleted = $this->schemaRepository->pruneSnapshots(10);

  expect($deleted)->toBe(0)
    ->and(File::glob($this->snapshotPath . '/*.json'))->toHaveCount(5);
});

it('keeps the newest snapshots when pruning', function () {
  /** @var UnitTestCase $this */

  $snapshots = [
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::parse('2024-01-15 10:00:00')
    ),
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::parse('2024-01-15 12:00:00')
    ),
    new SchemaSnapshot(
      connection: 'mysql',
      tables: [],
      createdAt: CarbonImmutable::parse('2024-01-15 14:00:00')
    ),
  ];

  foreach ($snapshots as $i => $snapshot) {
    $this->schemaRepository->saveSnapshot($snapshot);

    $file = File::glob($this->snapshotPath . '/*.json')[$i];
    touch($file, time() + $i); // oldest → newest
  }

  $this->schemaRepository->pruneSnapshots(2);

  $remaining = $this->schemaRepository->getAllSnapshots();

  expect($remaining)->toHaveCount(2)
    ->and($remaining[0]->createdAt->timestamp)
    ->toBe($snapshots[2]->createdAt->timestamp)
    ->and($remaining[1]->createdAt->timestamp)
    ->toBe($snapshots[1]->createdAt->timestamp);
});

it('can save and retrieve snapshot with table data', function () {
  /** @var UnitTestCase $this */

  $snapshot = new SchemaSnapshot(
    connection: 'mysql',
    tables: [
      new TableDefinition(
        name: 'users',
        columns: [
          new ColumnDefinition(
            name: 'id',
            type: 'bigint',
            nullable: false,
          ),
          new ColumnDefinition(
            name: 'email',
            type: 'varchar',
            nullable: false,
          ),
        ]
      ),
      new TableDefinition(
        name: 'posts',
        columns: [
          new ColumnDefinition(
            name: 'id',
            type: 'bigint',
            nullable: false,
          ),
        ]
      )
    ],
    createdAt: CarbonImmutable::now()
  );

  $this->schemaRepository->saveSnapshot($snapshot);
  $retrieved = $this->schemaRepository->getLatestSnapshot();

  expect($retrieved)->not->toBeNull()
    ->and($retrieved->tables)->toHaveCount(2)
    ->and($retrieved->tables[0]->name)->toBe('users')
    ->and($retrieved->tables[0]->columns)->toHaveCount(2)
    ->and($retrieved->tables[1]->name)->toBe('posts');
});

it('handles empty snapshot directory gracefully', function () {
  /** @var UnitTestCase $this */

  // Delete the directory to test handling
  File::deleteDirectory($this->snapshotPath);

  $snapshots = $this->schemaRepository->getAllSnapshots();
  $latest = $this->schemaRepository->getLatestSnapshot();

  expect($snapshots)->toBe([])
    ->and($latest)->toBeNull();
});

it('returns zero deleted when pruning empty directory', function () {
  /** @var UnitTestCase $this */

  $deleted = $this->schemaRepository->pruneSnapshots(10);

  expect($deleted)->toBe(0);
});