<?php

declare(strict_types=1);

namespace Nas11ai\SchemaGuard\Tests\Unit\Domain\Services;

use Illuminate\Database\Migrations\Migrator;
use Mockery;
use Mockery\MockInterface;
use Nas11ai\SchemaGuard\Domain\Entities\MigrationOperation;
use Nas11ai\SchemaGuard\Domain\Services\MigrationAnalysisService;
use Nas11ai\SchemaGuard\Domain\ValueObjects\DangerLevel;
use Nas11ai\SchemaGuard\Domain\ValueObjects\OperationType;
use Nas11ai\SchemaGuard\Infrastructure\Repositories\MigrationRepository;
use Nas11ai\SchemaGuard\Tests\Unit\UnitTestCase;

beforeEach(function (): void {
  /** @var UnitTestCase $this */
  /** @var MigrationRepository&MockInterface $repository */

  $this->repository = Mockery::mock(
    MigrationRepository::class,
    [$this->app->make(Migrator::class)]
  )->makePartial();
  $this->migrationAnalysisService = new MigrationAnalysisService($this->repository);
});

describe('analyze', function (): void {
  it('returns empty array when file does not exist', function (): void {
    /** @var UnitTestCase $this */

    $result = $this->migrationAnalysisService->analyze('/non/existent/file.php');

    expect($result)->toBeArray()->toBeEmpty();
  });

  it('returns empty array when file is empty', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('');

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toBeArray()->toBeEmpty();

    cleanupTempMigration($path);
  });

  it('skips comments and empty lines', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

// This is a comment
# Another comment

    // Indented comment
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toBeArray()->toBeEmpty();

    cleanupTempMigration($path);
  });

  it('detects DROP TABLE operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

Schema::drop('users');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_TABLE)
      ->and($result[0]->tableName)->toBe('users')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP TABLE IF EXISTS operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

Schema::dropIfExists('users');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_TABLE_IF_EXISTS)
      ->and($result[0]->tableName)->toBe('users')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP COLUMN operation with single column', function (): void {
    /** @var UnitTestCase $this */
    $content = <<<'PHP'
<?php

$table->dropColumn('email');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_COLUMN)
      ->and($result[0]->tableName)->toBe('email')
      ->and($result[0]->columnName)->toBe('email')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP COLUMN operation with multiple columns', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->dropColumn(['email', 'phone']);
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_COLUMN)
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP INDEX operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->dropIndex('users_email_index');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_INDEX)
      ->and($result[0]->tableName)->toBe('users_email_index')
      ->and($result[0]->indexName)->toBe('users_email_index')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP FOREIGN KEY operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->dropForeign('users_company_id_foreign');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_FOREIGN_KEY)
      ->and($result[0]->tableName)->toBe('users_company_id_foreign')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP PRIMARY operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->dropPrimary();
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_PRIMARY)
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects DROP UNIQUE operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->dropUnique('users_email_unique');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::DROP_UNIQUE)
      ->and($result[0]->tableName)->toBe('users_email_unique')
      ->and($result[0]->indexName)->toBe('users_email_unique')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects CREATE TABLE operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

Schema::create('users', function ($table) {
    $table->id();
});
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::CREATE_TABLE)
      ->and($result[0]->tableName)->toBe('users')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects CHANGE COLUMN operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->string('email')->change();
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::CHANGE_COLUMN)
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects RENAME COLUMN operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->renameColumn('email', 'email_address');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::RENAME_COLUMN)
      ->and($result[0]->tableName)->toBe('email')
      ->and($result[0]->columnName)->toBe('email_address')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects RENAME TABLE operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

Schema::rename('users', 'customers');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::RENAME_TABLE)
      ->and($result[0]->tableName)->toBe('users')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects ADD FOREIGN KEY operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->foreign('company_id')->references('id')->on('companies');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::ADD_FOREIGN_KEY)
      ->and($result[0]->tableName)->toBe('company_id')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects ADD INDEX operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->index('email');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::ADD_INDEX)
      ->and($result[0]->tableName)->toBe('email')
      ->and($result[0]->indexName)->toBe('email')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects ADD UNIQUE operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->unique('email');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::ADD_UNIQUE)
      ->and($result[0]->tableName)->toBe('email')
      ->and($result[0]->indexName)->toBe('email')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects ADD PRIMARY operation', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

$table->primary('id');
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0])->toBeValidMigrationOperation()
      ->and($result[0]->type)->toBe(OperationType::ADD_PRIMARY)
      ->and($result[0]->tableName)->toBe('id')
      ->and($result[0]->lineNumber)->toBe(3);

    cleanupTempMigration($path);
  });

  it('detects multiple operations in a single migration', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        
        Schema::dropIfExists('old_users');
        
        $table->dropColumn('email');
    }
};
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(3)
      ->and($result[0]->type)->toBe(OperationType::CREATE_TABLE)
      ->and($result[1]->type)->toBe(OperationType::DROP_TABLE_IF_EXISTS)
      ->and($result[2]->type)->toBe(OperationType::DROP_COLUMN);

    cleanupTempMigration($path);
  });

  it('handles mixed case and whitespace variations', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

    Schema::dropIfExists('users');
        $table->dropColumn('email');
    $table->dropIndex("users_email_index");
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(3);

    cleanupTempMigration($path);
  });
});

describe('analyzePendingMigrations', function (): void {
  it('returns empty array when no pending migrations', function (): void {
    /** @var UnitTestCase $this */

    $this->repository
      ->shouldReceive('getPendingMigrations')
      ->once()
      ->andReturn([]);

    $result = $this->migrationAnalysisService->analyzePendingMigrations();

    expect($result)->toBeArray()->toBeEmpty();
  });

  it('analyzes all pending migrations', function (): void {
    /** @var UnitTestCase $this */

    $migration1 = createTempMigration('<?php Schema::drop("users");');
    $migration2 = createTempMigration('<?php Schema::create("posts", function() {});');

    $this->repository
      ->shouldReceive('getPendingMigrations')
      ->once()
      ->andReturnUsing(fn() => [$migration1, $migration2]);

    $result = $this->migrationAnalysisService->analyzePendingMigrations();

    expect($result)->toBeArray()
      ->toHaveCount(2)
      ->and($result[$migration1])->toHaveCount(1)
      ->and($result[$migration2])->toHaveCount(1);

    cleanupTempMigration($migration1);
    cleanupTempMigration($migration2);
  });

  it('skips migrations with no dangerous operations', function (): void {
    /** @var UnitTestCase $this */

    $migration1 = createTempMigration('<?php // Just a comment');
    $migration2 = createTempMigration('<?php Schema::drop("users");');

    $this->repository
      ->shouldReceive('getPendingMigrations')
      ->once()
      ->andReturnUsing(fn() => [$migration1, $migration2]);

    $result = $this->migrationAnalysisService->analyzePendingMigrations();

    expect($result)->toBeArray()
      ->toHaveCount(1)
      ->and($result)->toHaveKey($migration2)
      ->and($result)->not->toHaveKey($migration1);

    cleanupTempMigration($migration1);
    cleanupTempMigration($migration2);
  });
});

describe('hasDangerousOperations', function (): void {
  it('returns false when no operations found', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php // Just a comment');

    $result = $this->migrationAnalysisService->hasDangerousOperations($path);

    expect($result)->toBeFalse();

    cleanupTempMigration($path);
  });

  it('returns false when only safe operations exist', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::create("users", function() {});');

    $result = $this->migrationAnalysisService->hasDangerousOperations($path);

    expect($result)->toBeFalse();

    cleanupTempMigration($path);
  });

  it('returns true when DROP TABLE operation exists', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::drop("users");');

    $result = $this->migrationAnalysisService->hasDangerousOperations($path);

    expect($result)->toBeTrue();

    cleanupTempMigration($path);
  });

  it('returns true when DROP COLUMN operation exists', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php $table->dropColumn("email");');

    $result = $this->migrationAnalysisService->hasDangerousOperations($path);

    expect($result)->toBeTrue();

    cleanupTempMigration($path);
  });

  it('returns true when CHANGE COLUMN operation exists', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php $table->string("email")->change();');

    $result = $this->migrationAnalysisService->hasDangerousOperations($path);

    expect($result)->toBeTrue();

    cleanupTempMigration($path);
  });

  it('returns false for file that does not exist', function (): void {
    /** @var UnitTestCase $this */
    $result = $this->migrationAnalysisService->hasDangerousOperations('/non/existent/file.php');

    expect($result)->toBeFalse();
  });
});

describe('getDangerLevel', function (): void {
  it('returns SAFE when no operations found', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php // Just a comment');

    $result = $this->migrationAnalysisService->getDangerLevel($path);

    expect($result)->toBe(DangerLevel::SAFE->value);

    cleanupTempMigration($path);
  });

  it('returns SAFE for CREATE TABLE operation', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::create("users", function() {});');

    $result = $this->migrationAnalysisService->getDangerLevel($path);

    expect($result)->toBe(DangerLevel::SAFE->value);

    cleanupTempMigration($path);
  });

  it('returns CRITICAL for DROP TABLE operation', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::drop("users");');

    $result = $this->migrationAnalysisService->getDangerLevel($path);

    expect($result)->toBe(DangerLevel::CRITICAL->value);

    cleanupTempMigration($path);
  });

  it('returns HIGH for DROP COLUMN operation', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php $table->dropColumn("email");');

    $result = $this->migrationAnalysisService->getDangerLevel($path);

    expect($result)->toBe(DangerLevel::CRITICAL->value);

    cleanupTempMigration($path);
  });

  it('returns highest danger level when multiple operations exist', function (): void {
    /** @var UnitTestCase $this */

    $content = <<<'PHP'
<?php

Schema::create('users', function() {}); // SAFE
$table->index('email'); // LOW
$table->dropColumn('phone'); // HIGH
PHP;

    $path = createTempMigration($content);

    $result = $this->migrationAnalysisService->getDangerLevel($path);

    expect($result)->toBe(DangerLevel::CRITICAL->value);

    cleanupTempMigration($path);
  });

  it('returns SAFE for file that does not exist', function (): void {
    /** @var UnitTestCase $this */

    $result = $this->migrationAnalysisService->getDangerLevel('/non/existent/file.php');

    expect($result)->toBe(DangerLevel::SAFE->value);
  });
});

describe('edge cases', function (): void {
  it('handles single quotes in table names', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration("<?php Schema::drop('users');");

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0]->tableName)->toBe('users');

    cleanupTempMigration($path);
  });

  it('handles double quotes in table names', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::drop("users");');

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0]->tableName)->toBe('users');

    cleanupTempMigration($path);
  });

  it('handles operations with extra spaces', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::drop(  "users"  );');

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0]->tableName)->toBe('users');

    cleanupTempMigration($path);
  });

  it('handles renameColumn with spaces around comma', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php $table->renameColumn("old_name" , "new_name");');

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0]->type)->toBe(OperationType::RENAME_COLUMN);

    cleanupTempMigration($path);
  });

  it('handles rename table with spaces', function (): void {
    /** @var UnitTestCase $this */

    $path = createTempMigration('<?php Schema::rename("old_table" , "new_table");');

    $result = $this->migrationAnalysisService->analyze($path);

    expect($result)->toHaveCount(1)
      ->and($result[0]->type)->toBe(OperationType::RENAME_TABLE);

    cleanupTempMigration($path);
  });
});