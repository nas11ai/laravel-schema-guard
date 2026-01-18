<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
  public function up(): void
  {
    // CRITICAL: Drop table
    Schema::dropIfExists('old_logs');

    // HIGH: Drop column
    Schema::table('users', function (Blueprint $table) {
      $table->dropColumn('old_field');
    });

    // MEDIUM: Drop index
    Schema::table('posts', function (Blueprint $table) {
      $table->dropIndex('posts_status_index');
    });
  }

  public function down(): void
  {
    // Rollback logic
  }
};