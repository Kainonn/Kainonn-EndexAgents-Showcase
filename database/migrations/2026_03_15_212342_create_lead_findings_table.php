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
        Schema::create('lead_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('agent_name');
            $table->string('stage');
            $table->text('summary');
            $table->json('evidence')->nullable();
            $table->json('payload')->nullable();
            $table->decimal('confidence', 5, 4)->nullable();
            $table->timestamps();

            $table->index(['lead_id', 'stage', 'agent_name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_findings');
    }
};
