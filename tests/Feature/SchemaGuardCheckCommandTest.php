<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;

beforeEach(function () {
  Config::set('schema-guard.enabled', true);
});

test('check command runs successfully when enabled', function () {
  $this->artisan('schema-guard:check')
    ->assertSuccessful();
});

test('check command warns when disabled', function () {
  Config::set('schema-guard.enabled', false);

  $this->artisan('schema-guard:check')
    ->expectsOutput('SchemaGuard is disabled. Enable it in config/schema-guard.php')
    ->assertFailed();
});

test('check command can save snapshot', function () {
  $this->artisan('schema-guard:check --save')
    ->expectsOutputToContain('Creating schema snapshot')
    ->assertSuccessful();

  // Verify snapshot was created
  $repository = app(SchemaRepository::class);
  $snapshot = $repository->getLatestSnapshot();

  expect($snapshot)->toBeInstanceOf(SchemaSnapshot::class);
});

test('check command shows no drift message when no baseline exists', function () {
  // Ensure no snapshots exist
  $repository = app(SchemaRepository::class);
  $snapshotPath = config('schema-guard.drift_detection.snapshot_path');

  if (is_dir($snapshotPath)) {
    array_map('unlink', glob("{$snapshotPath}/*.json"));
  }

  $this->artisan('schema-guard:check')
    ->expectsOutputToContain('No baseline snapshot found')
    ->assertSuccessful();
});