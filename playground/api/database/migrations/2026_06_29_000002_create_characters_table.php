<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Personajes (Character): cartas jugables con estadísticas. El coste es la suma
 * de poder+prestigio+intriga+dinero (se calcula al guardar). La defensa es
 * derivada (= coste), no se almacena.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->json('name');
            $table->json('description')->nullable();
            $table->json('ability')->nullable();
            $table->json('slug');
            $table->unsignedInteger('cost')->default(0);      // = power+prestige+intrigue+money
            $table->unsignedInteger('power')->default(0);      // poder
            $table->unsignedInteger('prestige')->default(0);   // prestigio
            $table->unsignedInteger('intrigue')->default(0);   // intriga
            $table->unsignedInteger('money')->default(0);      // dinero
            $table->boolean('is_published')->default(false);
            $table->datetimes();
            $table->softDeletesDatetime();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('characters');
    }
};
