<?php

use App\Models\Instituicao;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('instituicao show returns an Inertia page', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Escola Modelo',
        'sigla' => 'EM',
        'tipo' => 'Publica',
        'email' => 'contato@escolamodelo.test',
        'telefone' => '+244 222 000 111',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Principal, nº 1',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);

    $response = $this->actingAs($user)->get(route('instituicoes.show', $instituicao));

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('instituicoes/show')
        ->has('instituicao', fn (Assert $page) => $page
            ->where('id', $instituicao->id)
            ->where('nome', 'Escola Modelo')
            ->etc()
        )
        ->has('cursos')
    );
});
