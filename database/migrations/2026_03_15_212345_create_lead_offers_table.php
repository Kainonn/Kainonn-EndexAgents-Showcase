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
        Schema::create('lead_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('offer_type');
            $table->text('offer_summary')->nullable();
            $table->unsignedInteger('price_range_min')->nullable();
            $table->unsignedInteger('price_range_max')->nullable();
            $table->json('justification')->nullable();
            $table->string('recommended_by_agent')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'offer_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_offers');
    }
};
