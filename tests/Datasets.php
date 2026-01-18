<?php

declare(strict_types=1);

use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;
use Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType;

/**
 * Dataset: Danger Levels
 */
dataset('danger_levels', [
  'safe' => [DangerLevel::SAFE, 0],
  'low' => [DangerLevel::LOW, 1],
  'medium' => [DangerLevel::MEDIUM, 2],
  'high' => [DangerLevel::HIGH, 3],
  'critical' => [DangerLevel::CRITICAL, 4],
]);

/**
 * Dataset: Destructive Operations
 */
dataset('destructive_operations', [
  'drop table' => [OperationType::DROP_TABLE, true],
  'drop column' => [OperationType::DROP_COLUMN, true],
  'truncate' => [OperationType::TRUNCATE, true],
  'drop if exists' => [OperationType::DROP_TABLE_IF_EXISTS, true],
]);

/**
 * Dataset: Safe Operations
 */
dataset('safe_operations', [
  'create table' => [OperationType::CREATE_TABLE, false],
  'add column' => [OperationType::ADD_COLUMN, false],
  'add index' => [OperationType::ADD_INDEX, false],
  'add foreign key' => [OperationType::ADD_FOREIGN_KEY, false],
]);

/**
 * Dataset: Operations Requiring Backup
 */
dataset('operations_requiring_backup', [
  'drop table' => [OperationType::DROP_TABLE, true],
  'drop column' => [OperationType::DROP_COLUMN, true],
  'modify column' => [OperationType::MODIFY_COLUMN, true],
  'change column' => [OperationType::CHANGE_COLUMN, true],
  'drop primary' => [OperationType::DROP_PRIMARY, true],
  'drop foreign key' => [OperationType::DROP_FOREIGN_KEY, true],
]);

/**
 * Dataset: Operations NOT Requiring Backup
 */
dataset('operations_not_requiring_backup', [
  'create table' => [OperationType::CREATE_TABLE, false],
  'add column' => [OperationType::ADD_COLUMN, false],
  'add index' => [OperationType::ADD_INDEX, false],
  'rename table' => [OperationType::RENAME_TABLE, false],
]);

/**
 * Dataset: Column Types
 */
dataset('column_types', [
  'bigint' => ['bigint', false, false],
  'string' => ['string', false, false],
  'text' => ['text', false, false],
  'nullable string' => ['string', true, false],
  'auto increment bigint' => ['bigint', false, true],
]);

/**
 * Dataset: Table Names
 */
dataset('table_names', [
  'users',
  'posts',
  'comments',
  'categories',
  'tags',
]);

/**
 * Dataset: Migration File Patterns
 */
dataset('migration_patterns', [
  'create table' => ['Schema::create', OperationType::CREATE_TABLE],
  'drop table' => ['Schema::drop', OperationType::DROP_TABLE],
  'drop if exists' => ['Schema::dropIfExists', OperationType::DROP_TABLE_IF_EXISTS],
  'drop column' => ['->dropColumn', OperationType::DROP_COLUMN],
  'drop index' => ['->dropIndex', OperationType::DROP_INDEX],
  'drop foreign' => ['->dropForeign', OperationType::DROP_FOREIGN_KEY],
  'change column' => ['->change()', OperationType::CHANGE_COLUMN],
  'rename column' => ['->renameColumn', OperationType::RENAME_COLUMN],
]);

/**
 * Dataset: Sample Table Definitions
 */
dataset('sample_tables', function () {
  return [
    'simple table' => [
      [
        'name' => 'users',
        'columns' => [
          ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
          ['name' => 'name', 'type' => 'string', 'nullable' => false],
        ],
        'indexes' => [],
        'foreign_keys' => [],
      ],
    ],
    'table with indexes' => [
      [
        'name' => 'posts',
        'columns' => [
          ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
          ['name' => 'title', 'type' => 'string', 'nullable' => false],
          ['name' => 'status', 'type' => 'string', 'nullable' => false],
        ],
        'indexes' => [
          ['name' => 'posts_status_index', 'columns' => ['status']],
        ],
        'foreign_keys' => [],
      ],
    ],
    'table with foreign keys' => [
      [
        'name' => 'comments',
        'columns' => [
          ['name' => 'id', 'type' => 'bigint', 'nullable' => false],
          ['name' => 'post_id', 'type' => 'bigint', 'nullable' => false],
          ['name' => 'content', 'type' => 'text', 'nullable' => false],
        ],
        'indexes' => [],
        'foreign_keys' => [
          [
            'name' => 'comments_post_id_foreign',
            'columns' => ['post_id'],
            'foreign_table' => 'posts',
            'foreign_columns' => ['id'],
          ],
        ],
      ],
    ],
  ];
});

/**
 * Dataset: Environment Types
 */
dataset('environments', [
  'local',
  'development',
  'staging',
  'production',
]);

/**
 * Dataset: Database Drivers
 */
dataset('database_drivers', [
  'mysql',
  'pgsql',
]);