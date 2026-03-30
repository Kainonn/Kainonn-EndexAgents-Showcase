<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lead_contacts', function (Blueprint $table) {
            $table->text('contact_form_url')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('lead_contacts', function (Blueprint $table) {
            $table->string('contact_form_url')->nullable()->change();
        });
    }
};
