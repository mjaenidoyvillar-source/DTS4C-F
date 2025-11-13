<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('file_path');
            $table->string('file_mime')->nullable()->after('file_name');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_mime');
            $table->binary('file_data')->nullable()->after('file_size');
        });
    }

    public function down(): void
    {
        Schema::table('documents', function (Blueprint $table) {
            $table->dropColumn(['file_name', 'file_mime', 'file_size', 'file_data']);
        });
    }
};


