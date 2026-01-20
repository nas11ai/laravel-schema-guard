<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Schema;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

beforeEach(function () {
  /** @var UnitTestCase $this */
  Config::set('database.migrations', 'migrations');

  $this->migrator = Mockery::mock(Migrator::class);
  $this->repository = new MigrationRepository($this->migrator);
});

afterEach(function () {
  /** @var UnitTestCase $this */
  Schema::dropIfExists('migrations');
  Schema::dropIfExists('custom_table');
});

it('can get all migration files from multiple paths', function () {
  /** @var UnitTestCase $this */
  $paths = ['/path/one'];
  $this->migrator->shouldReceive('paths')->andReturn(...[$paths]);

  File::shouldReceive('exists')->andReturn(true);
  $mockFile = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
  $mockFile->shouldReceive('getExtension')->andReturn('php');
  $mockFile->shouldReceive('getRealPath')->andReturn('/path/one/test.php');

  File::shouldReceive('files')->andReturn(...[[$mockFile]]);

  expect($this->repository->getAllMigrations())->toHaveCount(1);
});

it('returns empty array if migration table does not exist', function () {
  /** @var UnitTestCase $this */
  // Don't create the table at all for this test
  expect($this->repository->getRanMigrations())->toBe([]);
});

it('can get list of ran migrations from database', function () {
  /** @var UnitTestCase $this */

  // Create table for this test
  Schema::create('migrations', function ($table) {
    $table->string('migration');
    $table->integer('batch');
  });

  DB::table('migrations')->insert([
    'migration' => 'migration_1',
    'batch' => 1,
  ]);

  expect($this->repository->getRanMigrations())->toBe(['migration_1']);
});

it('calculates pending migrations correctly', function () {
  /** @var UnitTestCase $this */
  /** @var MigrationRepository|MockInterface $repo */
  $repo = Mockery::mock(MigrationRepository::class, [$this->migrator])->makePartial();

  $repo->shouldReceive('getRanMigrations')->andReturn(...[['exists']]);
  $repo->shouldReceive('getAllMigrations')->andReturn(...[['exists', 'new']]);

  expect($repo->getPendingMigrations())->toBe(['new']);
});

it('returns zero for last batch if migration table is missing', function () {
  /** @var UnitTestCase $this */
  // Don't create the table at all for this test
  expect($this->repository->getLastBatchNumber())->toBe(0);
});

it('returns the maximum batch number from database', function () {
  /** @var UnitTestCase $this */

  // Create table for this test
  Schema::create('migrations', function ($table) {
    $table->string('migration');
    $table->integer('batch');
  });

  DB::table('migrations')->insert([
    ['migration' => 'm1', 'batch' => 1],
    ['migration' => 'm2', 'batch' => 3],
  ]);

  expect($this->repository->getLastBatchNumber())->toBe(3);
});

it('can get migrations only from a specific batch', function () {
  /** @var UnitTestCase $this */

  // Create table for this test
  Schema::create('migrations', function ($table) {
    $table->string('migration');
    $table->integer('batch');
  });

  DB::table('migrations')->insert([
    ['migration' => 'm1', 'batch' => 1],
    ['migration' => 'm2', 'batch' => 2],
  ]);

  expect($this->repository->getMigrationsFromBatch(1))->toBe(['m1']);
});

it('uses custom migration table name from config if available', function () {
  /** @var UnitTestCase $this */

  Schema::create('custom_table', function ($table) {
    $table->string('migration');
    $table->integer('batch');
  });

  Config::set('database.migrations', 'custom_table');

  DB::table('custom_table')->insert([
    'migration' => 'custom_m1',
    'batch' => 1,
  ]);

  expect($this->repository->getLastBatchNumber())->toBe(1);
});