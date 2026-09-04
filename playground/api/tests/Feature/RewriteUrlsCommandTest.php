<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

// motor:rewrite-urls — tras importar la BD de otro entorno, sustituye el
// origen de las URL absolutas del contenido (texto, JSON plano y JSON con
// barras escapadas) en todas las tablas; --dry-run solo cuenta.

beforeEach(function () {
    Schema::create('rewrite_probe', function (Blueprint $table) {
        $table->id();
        $table->string('title');
        $table->text('body')->nullable();
        $table->json('settings')->nullable();
        $table->integer('hits')->default(0);
    });

    DB::table('rewrite_probe')->insert([
        [
            'title' => 'http://localhost:8010/storage/a.png',
            'body' => '<p><img src="http://localhost:8010/storage/icon/1/dice.svg"> y otra http://localhost:8010/x</p>',
            // Como lo escribe json_encode: barras escapadas.
            'settings' => json_encode(['bg' => 'http://localhost:8010/storage/bg.jpg']),
            'hits' => 3,
        ],
        [
            'title' => 'sin urls',
            'body' => 'https://otro.dominio.com/localhost:8010',
            'settings' => json_encode(['bg' => null]),
            'hits' => 1,
        ],
    ]);
});

afterEach(fn () => Schema::dropIfExists('rewrite_probe'));

it('reescribe el origen en texto y JSON (plano y escapado) de todas las tablas', function () {
    $this->artisan('motor:rewrite-urls', ['from' => 'http://localhost:8010/', 'to' => 'https://juego.test'])
        ->expectsOutputToContain('rewrite_probe.title: 1 fila(s)')
        ->assertSuccessful();

    $row = DB::table('rewrite_probe')->find(1);
    expect($row->title)->toBe('https://juego.test/storage/a.png')
        ->and($row->body)->toBe('<p><img src="https://juego.test/storage/icon/1/dice.svg"> y otra https://juego.test/x</p>')
        ->and(json_decode($row->settings, true))->toBe(['bg' => 'https://juego.test/storage/bg.jpg'])
        ->and($row->hits)->toBe(3);

    // Lo que no lleva el origen no se toca (ni un dominio que lo contenga como texto suelto).
    $other = DB::table('rewrite_probe')->find(2);
    expect($other->body)->toBe('https://otro.dominio.com/localhost:8010')
        ->and($other->title)->toBe('sin urls');
});

it('con --dry-run cuenta pero no escribe, y sin coincidencias lo dice', function () {
    $this->artisan('motor:rewrite-urls', ['from' => 'http://localhost:8010', 'to' => 'https://juego.test', '--dry-run' => true])
        ->expectsOutputToContain('dry-run')
        ->assertSuccessful();
    expect(DB::table('rewrite_probe')->find(1)->title)->toBe('http://localhost:8010/storage/a.png');

    $this->artisan('motor:rewrite-urls', ['from' => 'http://nadie.test', 'to' => 'https://juego.test'])
        ->expectsOutputToContain('Nada que reescribir')
        ->assertSuccessful();

    $this->artisan('motor:rewrite-urls', ['from' => 'https://juego.test', 'to' => 'https://juego.test'])
        ->assertFailed();
});

it('con "/" deja las URL relativas a la raíz', function () {
    $this->artisan('motor:rewrite-urls', ['from' => 'http://localhost:8010', 'to' => '/'])
        ->expectsOutputToContain('rutas relativas')
        ->assertSuccessful();

    $row = DB::table('rewrite_probe')->find(1);
    expect($row->title)->toBe('/storage/a.png')
        ->and($row->body)->toBe('<p><img src="/storage/icon/1/dice.svg"> y otra /x</p>')
        ->and(json_decode($row->settings, true))->toBe(['bg' => '/storage/bg.jpg']);
});
