<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migrator;
use Illuminate\Support\Facades\File;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;
use Mockery\MockInterface;

beforeEach(function () {
    /** @var UnitTestCase $this */
    
    /** @var Migrator|MockInterface $migrator */
    $migrator = Mockery::mock(Migrator::class);
    $this->migrator = $migrator;

    $this->repository = new MigrationRepository($this->migrator);
});

it('can get all migrations from file system', function () {
    /** @var UnitTestCase $this */
    $paths = ['/database/migrations'];

    $this->migrator->shouldReceive('paths')->andReturn(...[$paths]);

    File::shouldReceive('exists')->with('/database/migrations')->andReturn(true);

    /** @var \Symfony\Component\Finder\SplFileInfo|MockInterface $mockFile */
    $mockFile = Mockery::mock(\Symfony\Component\Finder\SplFileInfo::class);
    $mockFile->shouldReceive('getExtension')->andReturn('php');
    $mockFile->shouldReceive('getRealPath')->andReturn('/database/migrations/2023_01_01_table.php');

    File::shouldReceive('files')->with('/database/migrations')->andReturn(...[[$mockFile]]);

    $result = $this->repository->getAllMigrations();

    expect($result)->toBeArray()->toHaveCount(1);
});

it('calculates pending migrations correctly', function () {
    /** @var UnitTestCase $this */
    
    /** @var MigrationRepository|MockInterface $repo */
    $repo = Mockery::mock(MigrationRepository::class, [$this->migrator])->makePartial();

    $repo->shouldReceive('getRanMigrations')->andReturn(...[['migration_1']]);
    $repo->shouldReceive('getAllMigrations')->andReturn(...[['migration_1', 'migration_2']]);

    $pending = $repo->getPendingMigrations();

    expect($pending)->toBe(['migration_2']);
});