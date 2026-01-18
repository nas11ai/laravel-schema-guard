<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Domain\ValueObjects;

enum OperationType: string
{
  // Table operations
  case CREATE_TABLE = 'createTable';
  case DROP_TABLE = 'dropTable';
  case RENAME_TABLE = 'renameTable';
  case DROP_TABLE_IF_EXISTS = 'dropIfExists';

  // Column operations
  case ADD_COLUMN = 'addColumn';
  case DROP_COLUMN = 'dropColumn';
  case MODIFY_COLUMN = 'modifyColumn';
  case RENAME_COLUMN = 'renameColumn';
  case CHANGE_COLUMN = 'change';

  // Index operations
  case ADD_INDEX = 'addIndex';
  case DROP_INDEX = 'dropIndex';
  case ADD_UNIQUE = 'addUnique';
  case DROP_UNIQUE = 'dropUnique';
  case ADD_PRIMARY = 'addPrimary';
  case DROP_PRIMARY = 'dropPrimary';

  // Foreign key operations
  case ADD_FOREIGN_KEY = 'addForeignKey';
  case DROP_FOREIGN_KEY = 'dropForeign';

  // Data operations
  case TRUNCATE = 'truncate';

  // Generic operations
  case ALTER = 'alter';
  case DROP = 'drop';

  /**
   * Get the danger level for this operation type.
   */
  public function getDangerLevel(): DangerLevel
  {
    return match ($this) {
      self::DROP_TABLE,
      self::DROP_TABLE_IF_EXISTS,
      self::TRUNCATE,
      self::DROP_COLUMN => DangerLevel::CRITICAL,

      self::DROP_PRIMARY,
      self::DROP_FOREIGN_KEY,
      self::MODIFY_COLUMN,
      self::CHANGE_COLUMN => DangerLevel::HIGH,

      self::DROP_INDEX,
      self::DROP_UNIQUE,
      self::RENAME_TABLE,
      self::RENAME_COLUMN => DangerLevel::MEDIUM,

      self::ADD_COLUMN,
      self::ADD_INDEX,
      self::ADD_UNIQUE,
      self::ADD_PRIMARY,
      self::ADD_FOREIGN_KEY => DangerLevel::LOW,

      self::CREATE_TABLE => DangerLevel::SAFE,

      default => DangerLevel::MEDIUM,
    };
  }

  /**
   * Check if this operation is destructive.
   */
  public function isDestructive(): bool
  {
    return in_array($this, [
      self::DROP_TABLE,
      self::DROP_TABLE_IF_EXISTS,
      self::DROP_COLUMN,
      self::TRUNCATE,
    ]);
  }

  /**
   * Check if this operation requires backup.
   */
  public function requiresBackup(): bool
  {
    return $this->isDestructive() || in_array($this, [
      self::MODIFY_COLUMN,
      self::CHANGE_COLUMN,
      self::DROP_PRIMARY,
      self::DROP_FOREIGN_KEY,
    ]);
  }

  /**
   * Get human-readable description.
   */
  public function getDescription(): string
  {
    return match ($this) {
      self::CREATE_TABLE => 'Creating a new table',
      self::DROP_TABLE => 'Dropping a table',
      self::DROP_TABLE_IF_EXISTS => 'Dropping a table if it exists',
      self::RENAME_TABLE => 'Renaming a table',
      self::ADD_COLUMN => 'Adding a new column',
      self::DROP_COLUMN => 'Dropping a column',
      self::MODIFY_COLUMN => 'Modifying a column',
      self::RENAME_COLUMN => 'Renaming a column',
      self::CHANGE_COLUMN => 'Changing a column definition',
      self::ADD_INDEX => 'Adding an index',
      self::DROP_INDEX => 'Dropping an index',
      self::ADD_UNIQUE => 'Adding a unique constraint',
      self::DROP_UNIQUE => 'Dropping a unique constraint',
      self::ADD_PRIMARY => 'Adding a primary key',
      self::DROP_PRIMARY => 'Dropping a primary key',
      self::ADD_FOREIGN_KEY => 'Adding a foreign key',
      self::DROP_FOREIGN_KEY => 'Dropping a foreign key',
      self::TRUNCATE => 'Truncating table data',
      self::ALTER => 'Altering table structure',
      self::DROP => 'Dropping database object',
    };
  }

  /**
   * Get the warning message for this operation.
   */
  public function getWarning(): ?string
  {
    return match ($this) {
      self::DROP_TABLE,
      self::DROP_TABLE_IF_EXISTS => 'This will permanently delete the table and ALL its data!',
      self::DROP_COLUMN => 'This will permanently delete the column and ALL its data!',
      self::TRUNCATE => 'This will delete ALL data in the table!',
      self::DROP_PRIMARY => 'Dropping the primary key may affect foreign key relationships!',
      self::MODIFY_COLUMN,
      self::CHANGE_COLUMN => 'Modifying the column may cause data loss if types are incompatible!',
      default => null,
    };
  }
}