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
        // Check if username column exists and name doesn't exist
        if (Schema::hasColumn('users', 'username') && !Schema::hasColumn('users', 'name')) {
            // Rename username to name
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('username', 'name');
            });
        } elseif (!Schema::hasColumn('users', 'name')) {
            // If neither exists, add name column
            Schema::table('users', function (Blueprint $table) {
                $table->string('name')->nullable()->after('id');
            });
        }
        
        // If username still exists after adding name, drop it
        if (Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('username');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Check if name exists, if so rename to username
        if (Schema::hasColumn('users', 'name') && !Schema::hasColumn('users', 'username')) {
            Schema::table('users', function (Blueprint $table) {
                $table->renameColumn('name', 'username');
            });
        }
    }
};
