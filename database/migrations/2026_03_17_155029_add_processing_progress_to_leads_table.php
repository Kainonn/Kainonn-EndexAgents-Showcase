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
        Schema::table('leads', function (Blueprint $table) {
            $table->boolean('is_processing')->default(false)->after('status');
            $table->string('processing_current_stage')->nullable()->after('is_processing');
            $table->unsignedTinyInteger('processing_stage_number')->nullable()->after('processing_current_stage');
            $table->unsignedTinyInteger('processing_total_stages')->nullable()->after('processing_stage_number');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropColumn(['is_processing', 'processing_current_stage', 'processing_stage_number', 'processing_total_stages']);
        });
    }
};
