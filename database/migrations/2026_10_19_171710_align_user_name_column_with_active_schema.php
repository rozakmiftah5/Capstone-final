<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasColumn('users', 'user_name') && ! Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('user_name', 'username');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasColumn('users', 'username') && ! Schema::hasColumn('users', 'user_name')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->renameColumn('username', 'user_name');
            });
        }
    }
};
