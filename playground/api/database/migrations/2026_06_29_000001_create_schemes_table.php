<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Argucias (Scheme): cartas jugables que pertenecen a una House. Título +
 * efecto (descripción) + coste + imagen. Traducibles y con slug por idioma.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schemes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('house_id')->constrained('houses')->cascadeOnDelete();
            $table->json('title');
            $table->json('description')->nullable();
            $table->json('slug');
            $table->unsignedInteger('cost')->default(0);
            $table->boolean('is_published')->default(false);
            $table->datetimes();
            $table->softDeletesDatetime();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schemes');
    }
};
