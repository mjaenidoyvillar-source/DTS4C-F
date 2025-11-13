<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('document_type')->after('title');
            $table->string('purpose')->after('description')->nullable();
            $table->foreignId('receiving_department_id')->nullable()->after('department_id')->constrained('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropConstrainedForeignId('receiving_department_id');
            $table->dropColumn('document_type');
            $table->dropColumn('purpose');
        });
    }
};


