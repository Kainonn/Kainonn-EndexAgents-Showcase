<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_status_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('from_status', 30);
            $table->string('to_status', 30);
            $table->string('reason', 500)->nullable();
            $table->timestamp('changed_at');
            $table->timestamps();

            $table->index(['lead_id', 'changed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_status_history');
    }
};
