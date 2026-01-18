<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard;

use Illuminate\Support\ServiceProvider;
use Nas11ai\SchemaGuard\Console\Commands\SchemaGuardAnalyzeCommand;
use Nas11ai\SchemaGuard\Console\Commands\SchemaGuardCheckCommand;
use Nas11ai\SchemaGuard\Console\Commands\SchemaGuardDryRunCommand;
use Nas11ai\SchemaGuard\Contracts\DatabaseAdapter;
use Nas11ai\SchemaGuard\Contracts\DriftDetector;
use Nas11ai\SchemaGuard\Contracts\MigrationAnalyzer;
use Nas11ai\SchemaGuard\Contracts\SchemaInspector;
use Nas11ai\SchemaGuard\Domain\Services\DriftDetectionService;
use Nas11ai\SchemaGuard\Domain\Services\MigrationAnalysisService;
use Nas11ai\SchemaGuard\Domain\Services\SafetyGuardService;
use Nas11ai\SchemaGuard\Infrastructure\Adapters\MySQLAdapter;
use Nas11ai\SchemaGuard\Infrastructure\Adapters\PostgreSQLAdapter;
use Nas11ai\SchemaGuard\Infrastructure\Inspectors\MySQLInspector;
use Nas11ai\SchemaGuard\Infrastructure\Inspectors\PostgreSQLInspector;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\SchemaRepository;

class SchemaGuardServiceProvider extends ServiceProvider
{
  /**
   * Register any application services.
   */
  public function register(): void
  {
    $this->mergeConfigFrom(
      __DIR__ . '/../config/schema-guard.php',
      'schema-guard'
    );

    $this->registerAdapters();
    $this->registerInspectors();
    $this->registerRepositories();
    $this->registerServices();
  }

  /**
   * Bootstrap any application services.
   */
  public function boot(): void
  {
    if ($this->app->runningInConsole()) {
      $this->publishes([
        __DIR__ . '/../config/schema-guard.php' => config_path('schema-guard.php'),
      ], 'schema-guard-config');

      $this->commands([
        SchemaGuardCheckCommand::class,
        SchemaGuardAnalyzeCommand::class,
        SchemaGuardDryRunCommand::class,
      ]);
    }
  }

  /**
   * Register database adapters.
   */
  protected function registerAdapters(): void
  {
    $this->app->bind(DatabaseAdapter::class, function ($app) {
      $driver = config('schema-guard.database.default', 'mysql');

      return match ($driver) {
        'mysql' => $app->make(MySQLAdapter::class),
        'pgsql' => $app->make(PostgreSQLAdapter::class),
        default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}"),
      };
    });

    $this->app->singleton(MySQLAdapter::class);
    $this->app->singleton(PostgreSQLAdapter::class);
  }

  /**
   * Register schema inspectors.
   */
  protected function registerInspectors(): void
  {
    $this->app->bind(SchemaInspector::class, function ($app) {
      $driver = config('schema-guard.database.default', 'mysql');

      return match ($driver) {
        'mysql' => $app->make(MySQLInspector::class),
        'pgsql' => $app->make(PostgreSQLInspector::class),
        default => throw new \InvalidArgumentException("Unsupported database driver: {$driver}"),
      };
    });

    $this->app->singleton(MySQLInspector::class);
    $this->app->singleton(PostgreSQLInspector::class);
  }

  /**
   * Register repositories.
   */
  protected function registerRepositories(): void
  {
    $this->app->singleton(SchemaRepository::class);
    $this->app->singleton(MigrationRepository::class);
  }

  /**
   * Register domain services.
   */
  protected function registerServices(): void
  {
    $this->app->singleton(DriftDetector::class, DriftDetectionService::class);
    $this->app->singleton(MigrationAnalyzer::class, MigrationAnalysisService::class);
    $this->app->singleton(SafetyGuardService::class);
  }
}