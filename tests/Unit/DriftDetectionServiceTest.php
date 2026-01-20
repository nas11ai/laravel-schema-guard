<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Nas11ai\SchemaGuard\Contracts\SchemaInspector;
use Nas11ai\SchemaGuard\Domain\Entities\ColumnDefinition;
use Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot;
use Nas11ai\SchemaGuard\Domain\Entities\TableDefinition;
use Nas11ai\SchemaGuard\Domain\Services\DriftDetectionService;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;

it('returns no drift when no baseline snapshot exists', function () {
  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $inspector->shouldReceive('getTables')->once()->andReturn([]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn(null);

  $service = new DriftDetectionService($inspector, $repository);

  $result = $service->detectDrift();

  expect($result['has_drift'])->toBeFalse()
    ->and($result['message'])->toContain('No baseline snapshot')
    ->and($result['current_snapshot'])->toBeInstanceOf(SchemaSnapshot::class);
});

it('detects no drift when schema is identical', function () {
  $table = new TableDefinition(
    name: 'users',
    columns: [
      new ColumnDefinition('id', 'bigint', false),
    ]
  );

  $baseline = SchemaSnapshot::create('mysql', [$table]);
  $current = SchemaSnapshot::create('mysql', [$table]);

  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $inspector->shouldReceive('getTables')->once()->andReturnUsing(fn() => [$table]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn($baseline);

  $service = new DriftDetectionService($inspector, $repository);

  $result = $service->detectDrift();

  expect($result['has_drift'])->toBeFalse()
    ->and($result['summary']['total_changes'])->toBe(0);
});

it('detects added tables', function () {
  $baselineTable = new TableDefinition('users', []);
  $newTable = new TableDefinition('posts', []);

  $baseline = SchemaSnapshot::create('mysql', [$baselineTable]);
  $current = SchemaSnapshot::create('mysql', [$baselineTable, $newTable]);

  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $inspector->shouldReceive('getTables')->once()->andReturnUsing(fn() => [$baselineTable, $newTable]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn($baseline);

  $service = new DriftDetectionService($inspector, $repository);

  $result = $service->detectDrift();

  expect($result['has_drift'])->toBeTrue()
    ->and($result['added_tables'])->toBe(['posts'])
    ->and($result['summary']['tables_added'])->toBe(1);
});

it('detects removed tables', function () {
  $users = new TableDefinition('users', []);
  $posts = new TableDefinition('posts', []);

  $baseline = SchemaSnapshot::create('mysql', [$users, $posts]);
  $current = SchemaSnapshot::create('mysql', [$users]);

  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $inspector->shouldReceive('getTables')->once()->andReturnUsing(fn() => [$users]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn($baseline);

  $service = new DriftDetectionService($inspector, $repository);

  $result = $service->detectDrift();

  expect($result['has_drift'])->toBeTrue()
    ->and($result['removed_tables'])->toBe(['posts'])
    ->and($result['summary']['tables_removed'])->toBe(1);
});

it('detects modified columns', function () {
  $expectedTable = new TableDefinition(
    'users',
    [new ColumnDefinition('email', 'varchar', false)]
  );

  $actualTable = new TableDefinition(
    'users',
    [new ColumnDefinition('email', 'varchar', true)]
  );

  $baseline = SchemaSnapshot::create('mysql', [$expectedTable]);
  $current = SchemaSnapshot::create('mysql', [$actualTable]);

  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $inspector->shouldReceive('getTables')->once()->andReturnUsing(fn() => [$actualTable]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn($baseline);

  $service = new DriftDetectionService($inspector, $repository);

  $result = $service->detectDrift();

  expect($result['has_drift'])->toBeTrue()
    ->and($result['modified_tables'])->toHaveKey('users')
    ->and($result['summary']['tables_modified'])->toBe(1);
});

it('hasDrift returns true when drift exists', function () {
  $inspector = Mockery::mock(SchemaInspector::class);
  $repository = Mockery::mock(SchemaRepository::class);

  $baseline = SchemaSnapshot::create('mysql', []);
  $currentTable = new TableDefinition('users', []);

  $inspector->shouldReceive('getTables')->once()->andReturnUsing(fn() => [$currentTable]);
  $repository->shouldReceive('getLatestSnapshot')->once()->andReturn($baseline);

  $service = new DriftDetectionService($inspector, $repository);

  expect($service->hasDrift())->toBeTrue();
});

