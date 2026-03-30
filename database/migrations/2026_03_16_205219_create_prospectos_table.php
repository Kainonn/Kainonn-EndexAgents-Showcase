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
        Schema::create('prospectos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('campaign_run_id')->nullable()->constrained()->nullOnDelete();
            $table->string('nombre', 255);
            $table->string('giro', 100)->nullable();
            $table->string('categoria', 100)->nullable();
            $table->decimal('calificacion', 2, 1)->nullable();
            $table->unsignedInteger('num_resenas')->nullable();
            $table->text('direccion')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->string('sitio_web', 500)->nullable();
            $table->text('horario')->nullable();
            $table->string('coordenadas', 100)->nullable();
            $table->string('ciudad', 100)->nullable();
            $table->string('estado', 100)->nullable();
            $table->string('pais', 60)->default('México');
            $table->string('fuente', 60)->default('Google Maps');
            $table->string('url_maps', 500)->nullable();
            $table->text('notas')->nullable();
            $table->boolean('contactado')->default(false);
            $table->timestamp('fecha_contacto')->nullable();
            $table->string('estatus', 40)->default('new');
            $table->timestamp('creado_en')->useCurrent();
            $table->timestamp('actualizado_en')->useCurrent()->useCurrentOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prospectos');
    }
};
