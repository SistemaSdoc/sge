<?php

use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new users can register', function () {
    $response = $this->post(route('register.store'), [
        'name' => 'Test User',
        'email' => 'test@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('central register accepts the current form field names', function () {
    $response = $this->post(route('central.register.store'), [
        'nome' => 'Escola Secundária de Luanda',
        'sigla' => 'ESL',
        'tipo' => 'colegio',
        'domain' => 'escola',
        'user_nome' => 'João da Silva',
        'user_email' => 'joao@escola.com',
        'password' => 'Password123!',
        'password_confirmation' => 'Password123!',
    ]);

    $response->assertSessionHasNoErrors();
    $response->assertRedirect();
});
