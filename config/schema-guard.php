<?php

declare(strict_types=1);

return [
  /*
  |--------------------------------------------------------------------------
  | SchemaGuard Enabled
  |--------------------------------------------------------------------------
  |
  | This option controls whether SchemaGuard is enabled. When disabled,
  | all safety checks and drift detection will be bypassed.
  |
  */
  'enabled' => env('SCHEMA_GUARD_ENABLED', true),

  /*
  |--------------------------------------------------------------------------
  | Database Configuration
  |--------------------------------------------------------------------------
  |
  | Specify the default database connection to use for schema inspection.
  | This should match one of your database connections in config/database.php
  |
  */
  'database' => [
    'default' => env('DB_CONNECTION', 'mysql'),
  ],

  /*
  |--------------------------------------------------------------------------
  | Safety Configuration
  |--------------------------------------------------------------------------
  |
  | Configure which operations are considered dangerous and require
  | additional confirmation before execution.
  |
  */
  'safety' => [
    /*
     * Operations that are considered dangerous and will trigger warnings
     */
    'dangerous_operations' => [
      'dropColumn',
      'dropTable',
      'dropIndex',
      'dropForeign',
      'dropPrimary',
      'dropUnique',
      'drop',
      'dropIfExists',
    ],

    /*
     * Operations that modify data and may cause data loss
     */
    'destructive_operations' => [
      'truncate',
      'dropColumn',
      'dropTable',
    ],

    /*
     * Require confirmation for dangerous operations
     */
    'require_confirmation' => env('SCHEMA_GUARD_REQUIRE_CONFIRMATION', true),

    /*
     * Prevent execution in production without explicit flag
     */
    'strict_mode_production' => env('SCHEMA_GUARD_STRICT_PRODUCTION', true),
  ],

  /*
  |--------------------------------------------------------------------------
  | Drift Detection Configuration
  |--------------------------------------------------------------------------
  |
  | Configure schema drift detection behavior and snapshot management.
  |
  */
  'drift_detection' => [
    /*
     * Automatically create snapshots after migrations
     */
    'auto_snapshot' => env('SCHEMA_GUARD_AUTO_SNAPSHOT', true),

    /*
     * Path where schema snapshots will be stored
     */
    'snapshot_path' => database_path('schema-snapshots'),

    /*
     * Tables to exclude from drift detection
     */
    'excluded_tables' => [
      'migrations',
    ],

    /*
     * Check these schema components for drift
     */
    'check_components' => [
      'tables' => true,
      'columns' => true,
      'indexes' => true,
      'foreign_keys' => true,
    ],
  ],

  /*
  |--------------------------------------------------------------------------
  | Dry Run Configuration
  |--------------------------------------------------------------------------
  |
  | Configure dry-run mode for testing migrations without execution.
  |
  */
  'dry_run' => [
    /*
     * Show SQL queries that would be executed
     */
    'show_sql' => env('SCHEMA_GUARD_DRY_RUN_SHOW_SQL', true),

    /*
     * Verbosity level (0 = minimal, 1 = normal, 2 = verbose)
     */
    'verbosity' => env('SCHEMA_GUARD_DRY_RUN_VERBOSITY', 1),
  ],

  /*
  |--------------------------------------------------------------------------
  | Supported Database Drivers
  |--------------------------------------------------------------------------
  |
  | List of database drivers that SchemaGuard currently supports.
  | Additional drivers can be added through custom adapters.
  |
  */
  'supported_drivers' => [
    'mysql',
    'pgsql',
  ],
];