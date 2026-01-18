<?php

declare(strict_types=1);

use Nas11ai\SchemaGuard\Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "uses()" function to bind a different classes or traits.
|
*/

uses(TestCase::class)->in('Feature', 'Unit', 'Integration');

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