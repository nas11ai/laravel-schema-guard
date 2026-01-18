<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Tests;

use Nas11ai\SchemaGuard\SchemaGuardServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
  /**
   * @param \Illuminate\Foundation\Application $app
   * @return array<int, class-string>
   */
  protected function getPackageProviders($app): array
  {
    return [
      SchemaGuardServiceProvider::class,
    ];
  }

  /**
   * @param \Illuminate\Foundation\Application $app
   */
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
  }
}