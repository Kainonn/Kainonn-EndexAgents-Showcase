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
        Schema::create('outreach_templates', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 50)->default('email');
            $table->string('template_key', 100)->default('default');
            $table->unsignedInteger('version')->default(1);
            $table->string('name');
            $table->string('subject_template')->nullable();
            $table->longText('body_template');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['channel', 'template_key', 'version'], 'outreach_templates_channel_key_version_unique');
            $table->index(['channel', 'template_key', 'is_active'], 'outreach_templates_lookup_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('outreach_templates');
    }
};
