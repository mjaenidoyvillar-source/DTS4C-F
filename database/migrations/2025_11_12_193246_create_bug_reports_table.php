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
        Schema::create('bug_reports', function (Blueprint $table) {
            $table->id();
            $table->string('error_type')->nullable(); // Exception class name
            $table->string('severity')->default('error'); // error, warning, critical
            $table->text('message');
            $table->text('file')->nullable();
            $table->integer('line')->nullable();
            $table->text('trace')->nullable(); // Stack trace
            $table->string('url')->nullable(); // Request URL
            $table->string('method')->nullable(); // HTTP method
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->text('request_data')->nullable(); // JSON encoded request data
            $table->string('status')->default('open'); // open, resolved, ignored
            $table->text('resolution_notes')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->integer('occurrence_count')->default(1); // Count how many times this error occurred
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('resolved_by')->references('id')->on('users')->onDelete('set null');
            $table->index('status');
            $table->index('severity');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bug_reports');
    }
};
