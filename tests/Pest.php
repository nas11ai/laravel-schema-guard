<?php

declare(strict_types=1);

use Nas11ai\SchemaGuard\Tests\TestCase;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;

/*
|--------------------------------------------------------------------------
| Test Case Bindings
|--------------------------------------------------------------------------
|
| The pest() function allows you to bind specific test case classes to
| different test directories. This provides proper base functionality
| and helpers for each test type.
|
*/

// Unit Tests - Pure PHPUnit tests with mocked dependencies
// Uses UnitTestCase which extends PHPUnit\Framework\TestCase directly
pest()->extend(UnitTestCase::class)->in('Unit');

uses()
  ->beforeEach(fn() => Mockery::getConfiguration()->allowMockingNonExistentMethods(false))
  ->afterEach(fn() => Mockery::close())
  ->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
  return $this->toBe(1);
});

expect()->extend('toBeValidSnapshot', function () {
  expect($this->value)
    ->toBeInstanceOf(\Nas11ai\SchemaGuard\Domain\Entities\SchemaSnapshot::class)
    ->and($this->value->connection)->toBeString()
    ->and($this->value->tables)->toBeArray()
    ->and($this->value->createdAt)->toBeInstanceOf(\Carbon\CarbonImmutable::class);

  return $this;
});

expect()->extend('toBeValidTableDefinition', function () {
  expect($this->value)
    ->toBeInstanceOf(\Nas11ai\SchemaGuard\Domain\Entities\TableDefinition::class)
    ->and($this->value->name)->toBeString()
    ->and($this->value->columns)->toBeArray();

  return $this;
});

expect()->extend('toBeValidMigrationOperation', function () {
  expect($this->value)
    ->toBeInstanceOf(\Nas11ai\SchemaGuard\Domain\Entities\MigrationOperation::class)
    ->and($this->value->type)->toBeInstanceOf(\Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType::class)
    ->and($this->value->tableName)->toBeString()
    ->and($this->value->lineNumber)->toBeInt();

  return $this;
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

// Helper function to skip tests based on database driver
function skipIfNotDriver(string $driver): void
{
  if (DB::getDriverName() !== $driver) {
    test()->markTestSkipped("{$driver} tests require {$driver} database");
  }
}

/**
 * Create a temporary test file.
 */
function createTempFile(string $content = '', string $extension = 'txt'): string
{
  $tempFile = tempnam(sys_get_temp_dir(), 'test_') . '.' . $extension;
  file_put_contents($tempFile, $content);

  return $tempFile;
}

/**
 * Delete a temporary file.
 */
function deleteTempFile(string $path): void
{
  if (file_exists($path)) {
    unlink($path);
  }
}

// Helper function to create a temporary migration file
function createTempMigration(string $content): string
{
  $filename = 'test_migration_' . uniqid() . '.php';
  $path = sys_get_temp_dir() . '/' . $filename;
  file_put_contents($path, $content);

  return $path;
}

// Helper function to clean up temporary migration
function cleanupTempMigration(string $path): void
{
  if (file_exists($path)) {
    unlink($path);
  }
}