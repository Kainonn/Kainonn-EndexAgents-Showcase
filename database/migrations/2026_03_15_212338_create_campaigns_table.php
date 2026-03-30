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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('solution_name');
            $table->text('description')->nullable();
            $table->json('target_segments')->nullable();
            $table->json('target_regions')->nullable();
            $table->json('pain_points')->nullable();
            $table->json('opportunity_signals')->nullable();
            $table->json('allowed_offers')->nullable();
            $table->string('commercial_tone')->nullable();
            $table->string('status')->default('draft')->index();
            $table->json('operational_limits')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
