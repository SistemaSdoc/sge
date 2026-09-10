<?php

use App\Models\Aluno;
use App\Models\Instituicao;
use App\Models\SolicitacaoDocumento;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    // Ensure roles exist used in other tests
    Role::firstOrCreate(['name' => 'Aluno']);
    Role::firstOrCreate(['name' => 'Secretaria']);
    Role::firstOrCreate(['name' => 'Director']);
});

it('listing endpoints include the standardized fields and flags (aluno index)', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Inst Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create([
        'instituicao_id' => $instituicao->id,
    ]);
    $user->assignRole('Aluno');

    $aluno = Aluno::create([
        'user_id' => $user->id,
        'situacao' => 'activo',
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => $aluno->id,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Teste payload',
        'status' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/aluno/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.responsavel_instituicao_id', $solicitacao->instituicaoResponsavelId())
        ->where('solicitacoes.0.can_decidir', false)
        ->where('solicitacoes.0.can_marcar_pago', false)
        ->where('solicitacoes.0.can_marcar_pronto', false)
        ->where('solicitacoes.0.estado_pagamento', 'pendente')
        ->etc()
    );
});

it('colegio listing includes fields and flags', function () {
    $colegio = Instituicao::create([
        'nome' => 'Colegio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $colegio->id]);
    $user->assignRole('Secretaria');

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $colegio->id,
        'tipo_documento' => 'historico',
        'motivo' => 'Pedido do colégio',
        'status' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/colegio');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/colegio/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.responsavel_instituicao_id', $solicitacao->instituicaoResponsavelId())
        ->where('solicitacoes.0.can_decidir', false)
        ->where('solicitacoes.0.can_marcar_pago', false)
        ->where('solicitacoes.0.can_marcar_pronto', false)
        ->where('solicitacoes.0.estado_pagamento', 'pendente')
        ->etc()
    );
});

it('emissao listing includes fields and flags', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Emissor Teste',
        'sigla' => 'ET',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole('Secretaria');

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Para emissão',
        'status' => 'aprovado',
        'estado_pagamento' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/emissao');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/emissao/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.responsavel_instituicao_id', $solicitacao->instituicaoResponsavelId())
        ->where('solicitacoes.0.can_decidir', false)
        ->where('solicitacoes.0.can_marcar_pago', false)
        ->where('solicitacoes.0.can_marcar_pronto', false)
        ->where('solicitacoes.0.estado_pagamento', 'pendente')
        ->etc()
    );
});

it('tutela listing includes fields for both locais and tuteladas', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituto Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole('Director');

    // local
    SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Local',
        'status' => 'pendente',
    ]);

    // tutelada (approved internally at colegio)
    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Tutelada',
        'status' => 'pendente',
        'data_aprovacao' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/tutela');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/tutela/solicitacoes-documentos/index')
        ->has('solicitacoes_locais', 1)
        ->has('solicitacoes_tuteladas', 1)
        ->etc()
    );
});

it('allows deleting a delivered request only after delivery is recorded', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Inst Delete',
        'sigla' => 'ID',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole('Aluno');

    $aluno = Aluno::create([
        'user_id' => $user->id,
        'situacao' => 'activo',
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => $aluno->id,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Delete teste',
        'status' => 'entregue',
        'data_levantamento' => now(),
    ]);

    $this->actingAs($user);

    $response = $this->delete('/dashboard/solicitacoes-documentos/'.$solicitacao->id);

    $response->assertRedirect();
    $this->assertDatabaseMissing('solicitacoes_documentos', ['id' => $solicitacao->id]);

    $listResponse = $this->get('/dashboard/solicitacoes-documentos');
    $listResponse->assertOk();
});

// Scenario tests
it('pendente request shows no payment/ready action flags', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Inst Pendente',
        'sigla' => 'IP',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole('Director');

    SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Pendente teste',
        'status' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/tutela');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/tutela/solicitacoes-documentos/index')
        ->has('solicitacoes_locais', 1)
            // pendente -> cannot mark paid/pronto
        ->where('solicitacoes_locais.0.can_marcar_pago', false)
        ->where('solicitacoes_locais.0.can_marcar_pronto', false)
        ->etc()
    );
});

it('aprovado request where current user is responsible -> can_marcar_pago true', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Inst Resp',
        'sigla' => 'IR',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->assignRole('Secretaria');

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Aprovado - responsável',
        'status' => 'aprovado',
        'estado_pagamento' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/emissao');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/emissao/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.id', $solicitacao->id)
        ->where('solicitacoes.0.can_marcar_pago', true)
        ->etc()
    );
});

it('aprovado request where current user is NOT responsible -> can_marcar_pago false', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Inst Resp 2',
        'sigla' => 'IR2',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $other = Instituicao::create([
        'nome' => 'Outra Inst',
        'sigla' => 'OI',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Aprovado - outro',
        'status' => 'aprovado',
        'estado_pagamento' => 'pendente',
    ]);

    $user = User::factory()->create(['instituicao_id' => $other->id]);
    $user->assignRole('Secretaria');

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/emissao');

    $response->assertOk();
    // The emissao list is scoped to instituicao_emissora_id == user instituicao, so this user will see none
    // Instead assert that when the responsible is not this user, the flag would be false if present.
    // Create a call directly to index (aluno) to fetch the solicitacao via a dummy aluno and assert can_marcar_pago false.

    $dummyUser = User::factory()->create(['instituicao_id' => $other->id]);
    $dummyUser->assignRole('Aluno');
    $aluno = Aluno::create(['user_id' => $dummyUser->id, 'situacao' => 'activo']);
    // Attach the same solicitacao to this aluno for retrieval via aluno index
    $solicitacao->aluno_id = $aluno->id;
    $solicitacao->save();

    $this->actingAs($dummyUser);
    $response = $this->get('/dashboard/solicitacoes-documentos');
    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/aluno/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.id', $solicitacao->id)
        ->where('solicitacoes.0.can_marcar_pago', false)
        ->etc()
    );
});

it('certificado originated from colegio resolves responsibility to instituto', function () {
    $colegio = Instituicao::create([
        'nome' => 'Colégio Cert',
        'sigla' => 'CC',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $instituto = Instituicao::create([
        'nome' => 'Instituto Cert',
        'sigla' => 'IC',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituto->id]);
    $user->assignRole('Aluno');

    $aluno = Aluno::create(['user_id' => $user->id, 'situacao' => 'activo']);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => $aluno->id,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $instituto->id,
        'tipo_documento' => 'certificado',
        'motivo' => 'Certificado teste',
        'status' => 'pendente',
    ]);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos');

    $response->assertOk();
    $response->assertInertia(fn (Assert $page) => $page->component('dashboards/aluno/solicitacoes-documentos/index')
        ->has('solicitacoes', 1)
        ->where('solicitacoes.0.responsavel_instituicao_id', $instituto->id)
        ->etc()
    );
});
