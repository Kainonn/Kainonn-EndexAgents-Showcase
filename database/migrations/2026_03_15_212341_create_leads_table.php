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
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->foreignId('campaign_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('company_name');
            $table->string('website_url')->nullable();
            $table->string('city')->nullable();
            $table->string('sector')->nullable();
            $table->string('source')->default('manual');
            $table->string('status')->default('detected')->index();
            $table->unsignedTinyInteger('priority')->default(50);
            $table->decimal('latest_confidence', 5, 4)->nullable();
            $table->timestamps();

            $table->index(['campaign_id', 'campaign_run_id', 'status']);
            $table->index(['company_name', 'website_url']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};
