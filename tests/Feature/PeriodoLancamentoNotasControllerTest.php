<?php

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\PeriodoLancamentoNotas;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('director can open the launch period settings page', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituicao',
        'email' => 'teste@instituicao.test',
        'telefone' => '+244 900 000 000',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);

    AnoLectivo::create([
        'data_inicio' => now()->subMonths(2)->startOfMonth(),
        'data_fim' => now()->addMonths(10)->endOfMonth(),
        'activo' => true,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    Role::findOrCreate('Director');
    Permission::findOrCreate('pautas.gerirPrazos');
    $user->assignRole('Director');
    $user->givePermissionTo('pautas.gerirPrazos');

    $response = $this->actingAs($user)->get(
        "/dashboard/instituicoes/{$instituicao->id}/prazos-lancamento-notas",
    );

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('pautas/prazos-lancamento-notas/edit')
        ->where('instituicao.id', $instituicao->id)
        ->where('periodoInicial', 1)
        ->has('periodos', 3)
    );
});

test('director can save launch periods for the active year', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituicao',
        'email' => 'teste@instituicao.test',
        'telefone' => '+244 900 000 000',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subMonths(2)->startOfMonth(),
        'data_fim' => now()->addMonths(10)->endOfMonth(),
        'activo' => true,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    Role::findOrCreate('Director');
    Permission::findOrCreate('pautas.gerirPrazos');
    $user->assignRole('Director');
    $user->givePermissionTo('pautas.gerirPrazos');

    $payload = [
        'periodo' => 1,
        'data_inicio' => now()->startOfMonth()->toDateString(),
        'data_limite' => now()->addDays(14)->toDateString(),
    ];

    $response = $this->actingAs($user)->put(
        "/dashboard/instituicoes/{$instituicao->id}/prazos-lancamento-notas",
        $payload,
    );

    $response->assertRedirect();

    expect(
        PeriodoLancamentoNotas::where('instituicao_id', $instituicao->id)
            ->where('ano_lectivo_id', $anoLectivo->id)
            ->count(),
    )->toBe(1);

    $this->assertDatabaseHas('periodo_lancamento_notas', [
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'periodo' => 1,
        'data_limite' => now()->addDays(14)->toDateString(),
    ]);
});

test('director cannot save a later launch period before the previous one exists', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'instituicao',
        'email' => 'teste@instituicao.test',
        'telefone' => '+244 900 000 000',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);

    AnoLectivo::create([
        'data_inicio' => now()->subMonths(2)->startOfMonth(),
        'data_fim' => now()->addMonths(10)->endOfMonth(),
        'activo' => true,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    Role::findOrCreate('Director');
    Permission::findOrCreate('pautas.gerirPrazos');
    $user->assignRole('Director');
    $user->givePermissionTo('pautas.gerirPrazos');

    $response = $this->actingAs($user)->put(
        "/dashboard/instituicoes/{$instituicao->id}/prazos-lancamento-notas",
        [
            'periodo' => 2,
            'data_inicio' => now()->startOfMonth()->toDateString(),
            'data_limite' => now()->addDays(14)->toDateString(),
        ],
    );

    $response->assertSessionHasErrors('periodo');
    $this->assertDatabaseCount('periodo_lancamento_notas', 0);
});
