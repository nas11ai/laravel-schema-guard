<?php

namespace Nas11ai\SchemaGuard\Tests\Unit;

use Illuminate\Database\Migrations\Migrator;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;
use Nas11ai\SchemaGuard\Domain\Services\MigrationAnalysisService;
use Orchestra\Testbench\TestCase as Orchestra;
use Nas11ai\SchemaGuard\SchemaGuardServiceProvider;
use Mockery\MockInterface;

/**
 * @property Migrator|MockInterface $migrator
 * @property MigrationRepository&MockInterface $repository
 * @property SchemaRepository $schemaRepository
 * 
 * @property MigrationAnalysisService $migrationAnalysisService
 * 
 * @property string $snapshotPath
 */

class UnitTestCase extends Orchestra
{
  public Migrator|MockInterface $migrator;

  protected function setUp(): void
  {
    parent::setUp();

    // Membuat direktori migrations sementara untuk testing
    if (!file_exists(__DIR__ . '/tmp/migrations')) {
      mkdir(__DIR__ . '/tmp/migrations', 0777, true);
    }
  }

  protected function tearDown(): void
  {
    // Hapus file sementara setelah test selesai
    array_map('unlink', glob(__DIR__ . '/tmp/migrations/*.php'));
    parent::tearDown();
  }

  protected function getPackageProviders($app)
  {
    return [
      SchemaGuardServiceProvider::class,
    ];
  }

  protected function getEnvironmentSetUp($app): void
  {
    // Setup default database to use sqlite :memory:
    $app['config']->set('database.default', 'testing');
    $app['config']->set('database.connections.testing', [
      'driver' => 'sqlite',
      'database' => ':memory:',
      'prefix' => '',
    ]);

    // Setup MySQL testing connection
    $app['config']->set('database.connections.mysql_testing', [
      'driver' => 'mysql',
      'host' => env('MYSQL_HOST', '127.0.0.1'),
      'port' => env('MYSQL_PORT', '3306'),
      'database' => env('MYSQL_DATABASE', 'schema_guard_test'),
      'username' => env('MYSQL_USERNAME', 'root'),
      'password' => env('MYSQL_PASSWORD', ''),
      'charset' => 'utf8mb4',
      'collation' => 'utf8mb4_unicode_ci',
      'prefix' => '',
      'strict' => true,
    ]);

    // Setup PostgreSQL testing connection
    $app['config']->set('database.connections.pgsql_testing', [
      'driver' => 'pgsql',
      'host' => env('PGSQL_HOST', '127.0.0.1'),
      'port' => env('PGSQL_PORT', '5432'),
      'database' => env('PGSQL_DATABASE', 'schema_guard_test'),
      'username' => env('PGSQL_USERNAME', 'postgres'),
      'password' => env('PGSQL_PASSWORD', ''),
      'charset' => 'utf8',
      'prefix' => '',
      'schema' => 'public',
      'sslmode' => 'prefer',
    ]);

    // Setup snapshot path for testing
    $app['config']->set('schema-guard.drift_detection.snapshot_path', database_path('schema-snapshots'));
  }
}