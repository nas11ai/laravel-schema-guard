<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    Schema::table('users', function (Blueprint $table) {
      // Add new column - LOW danger
      $table->string('phone')->nullable()->after('email');
      $table->timestamp('last_login_at')->nullable();

      // Change column type - HIGH danger (potential data loss)
      $table->text('email')->change();

      // Rename column - MEDIUM danger
      $table->renameColumn('name', 'full_name');

      // Add index - LOW danger
      $table->index('last_login_at');
    });
  }

  public function down(): void
  {
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn(['phone', 'last_login_at']);
      $table->string('email')->change();
      $table->renameColumn('full_name', 'name');
      $table->dropIndex(['last_login_at']);
    });
  }
};