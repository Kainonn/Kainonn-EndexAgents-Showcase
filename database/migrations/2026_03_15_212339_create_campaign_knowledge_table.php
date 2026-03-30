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
        Schema::create('campaign_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('file_name');
            $table->string('file_type');
            $table->string('storage_path')->nullable();
            $table->longText('raw_content');
            $table->longText('parsed_content')->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['campaign_id', 'file_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaign_knowledge');
    }
};
