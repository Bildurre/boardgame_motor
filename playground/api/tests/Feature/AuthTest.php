<?php

use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (config('motor.auth.roles') as $role) {
        Role::findOrCreate($role, 'web');
    }
});

it('registra un usuario con rol user y devuelve token', function () {
    $response = $this->postJson('/api/auth/register', [
        'name' => 'Egoi',
        'email' => 'egoi@example.com',
        'password' => 'secret-123',
        'password_confirmation' => 'secret-123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'egoi@example.com')
        ->assertJsonPath('user.roles.0', 'user')
        ->assertJsonPath('user.can_access_admin', false)
        ->assertJsonStructure(['token']);
});

it('rechaza el registro cuando el juego es solo-invitación', function () {
    config(['motor.auth.registration' => 'invite']);

    $this->postJson('/api/auth/register', [
        'name' => 'Egoi',
        'email' => 'egoi@example.com',
        'password' => 'secret-123',
        'password_confirmation' => 'secret-123',
    ])->assertForbidden();
});

it('hace login con credenciales válidas', function () {
    $user = motorUser();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertOk()->assertJsonStructure(['user', 'token']);
});

it('rechaza credenciales incorrectas', function () {
    $user = motorUser();

    $this->postJson('/api/auth/login', [
        'email' => $user->email,
        'password' => 'nope-nope',
    ])->assertUnprocessable()->assertJsonValidationErrors('email');
});

it('devuelve el usuario autenticado en /auth/me', function () {
    $user = motorUser('admin');

    $this->actingAs($user)->getJson('/api/auth/me')
        ->assertOk()
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.can_access_admin', true);
});

it('cierra sesión revocando el token actual', function () {
    $user = motorUser();
    $token = $user->createToken('bgm')->plainTextToken;

    $this->withToken($token)->postJson('/api/auth/logout')->assertOk();

    expect($user->tokens()->count())->toBe(0);
});

it('permite el admin a admin y editor pero no a user ni invitados', function () {
    $this->getJson('/api/admin/ping')->assertUnauthorized();

    $this->actingAs(motorUser('user'))->getJson('/api/admin/ping')->assertForbidden();
    $this->actingAs(motorUser('editor'))->getJson('/api/admin/ping')->assertOk();
    $this->actingAs(motorUser('admin'))->getJson('/api/admin/ping')->assertOk();
});
