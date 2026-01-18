<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Config;

beforeEach(function () {
  Config::set('schema-guard.enabled', true);
});

test('analyze command runs successfully when enabled', function () {
  $this->artisan('schema-guard:analyze')
    ->assertSuccessful();
});

test('analyze command warns when disabled', function () {
  Config::set('schema-guard.enabled', false);

  $this->artisan('schema-guard:analyze')
    ->expectsOutput('SchemaGuard is disabled. Enable it in config/schema-guard.php')
    ->assertFailed();
});

test('analyze command shows message when no pending migrations', function () {
  $this->artisan('schema-guard:analyze')
    ->expectsOutputToContain('No pending migrations to analyze')
    ->assertSuccessful();
});

test('analyze command fails for non-existent migration file', function () {
  $this->artisan('schema-guard:analyze /path/to/nonexistent.php')
    ->expectsOutputToContain('Migration file not found')
    ->assertFailed();
});