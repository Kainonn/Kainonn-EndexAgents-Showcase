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
        Schema::create('lead_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('total_score');
            $table->unsignedTinyInteger('urgency_score')->nullable();
            $table->unsignedTinyInteger('fit_score')->nullable();
            $table->unsignedTinyInteger('payment_capacity_score')->nullable();
            $table->json('rationale')->nullable();
            $table->string('scored_by_agent')->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'total_score']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_scores');
    }
};
