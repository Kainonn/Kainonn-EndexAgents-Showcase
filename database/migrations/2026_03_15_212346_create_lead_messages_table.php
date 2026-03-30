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
        Schema::create('lead_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->string('channel')->default('email');
            $table->string('subject')->nullable();
            $table->longText('body');
            $table->string('tone')->nullable();
            $table->string('generated_by_agent')->nullable();
            $table->unsignedSmallInteger('version')->default(1);
            $table->timestamps();

            $table->index(['lead_id', 'channel', 'version']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_messages');
    }
};
