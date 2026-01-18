<?php

declare(strict_types=1);

use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;
use Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType;

test('drop operations are critical', function () {
  expect(OperationType::DROP_TABLE->getDangerLevel())->toBe(DangerLevel::CRITICAL)
    ->and(OperationType::DROP_COLUMN->getDangerLevel())->toBe(DangerLevel::CRITICAL)
    ->and(OperationType::TRUNCATE->getDangerLevel())->toBe(DangerLevel::CRITICAL);
});

test('create operations are safe', function () {
  expect(OperationType::CREATE_TABLE->getDangerLevel())->toBe(DangerLevel::SAFE);
});

test('add operations are low danger', function () {
  expect(OperationType::ADD_COLUMN->getDangerLevel())->toBe(DangerLevel::LOW)
    ->and(OperationType::ADD_INDEX->getDangerLevel())->toBe(DangerLevel::LOW)
    ->and(OperationType::ADD_FOREIGN_KEY->getDangerLevel())->toBe(DangerLevel::LOW);
});

test('destructive operations are identified correctly', function () {
  expect(OperationType::DROP_TABLE->isDestructive())->toBeTrue()
    ->and(OperationType::DROP_COLUMN->isDestructive())->toBeTrue()
    ->and(OperationType::TRUNCATE->isDestructive())->toBeTrue()
    ->and(OperationType::CREATE_TABLE->isDestructive())->toBeFalse()
    ->and(OperationType::ADD_COLUMN->isDestructive())->toBeFalse();
});

test('operations requiring backup are identified correctly', function () {
  expect(OperationType::DROP_TABLE->requiresBackup())->toBeTrue()
    ->and(OperationType::DROP_COLUMN->requiresBackup())->toBeTrue()
    ->and(OperationType::MODIFY_COLUMN->requiresBackup())->toBeTrue()
    ->and(OperationType::CREATE_TABLE->requiresBackup())->toBeFalse()
    ->and(OperationType::ADD_INDEX->requiresBackup())->toBeFalse();
});

test('operation descriptions are human-readable', function () {
  expect(OperationType::DROP_TABLE->getDescription())->toBe('Dropping a table')
    ->and(OperationType::CREATE_TABLE->getDescription())->toBe('Creating a new table')
    ->and(OperationType::ADD_COLUMN->getDescription())->toBe('Adding a new column');
});

test('dangerous operations have warnings', function () {
  expect(OperationType::DROP_TABLE->getWarning())->toContain('permanently delete')
    ->and(OperationType::DROP_COLUMN->getWarning())->toContain('permanently delete')
    ->and(OperationType::TRUNCATE->getWarning())->toContain('ALL data')
    ->and(OperationType::CREATE_TABLE->getWarning())->toBeNull();
});