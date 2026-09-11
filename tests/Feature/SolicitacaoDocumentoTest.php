<?php

use App\Models\AnoLectivo;
use App\Models\Candidato;
use App\Models\Curso;
use App\Models\CursoTutelado;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\NivelEnsino;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Classe;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\SolicitacaoDocumento;
use App\Models\Turno;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Role;

it('aluno can access the document request page', function () {
    $role = Role::firstOrCreate(['name' => 'Aluno']);
    $user = User::factory()->create();
    $user->assignRole($role);

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos');

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboards/aluno/solicitacoes-documentos/index')
    );
});

it('calculates the responsible institution for the remaining workflow', function () {
    $instituto = Instituicao::create([
        'nome' => 'Instituto Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $pedidoLocal = SolicitacaoDocumento::create([
        'instituicao_origem_id' => $instituto->id,
        'instituicao_tutora_id' => $instituto->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Necessário para matrícula',
        'status' => 'pendente',
    ]);

    $pedidoColégio = SolicitacaoDocumento::create([
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $instituto->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Necessário para matrícula',
        'status' => 'pendente',
    ]);

    $pedidoCertificado = SolicitacaoDocumento::create([
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $instituto->id,
        'tipo_documento' => 'certificado',
        'motivo' => 'Necessário para matrícula',
        'status' => 'pendente',
    ]);

    expect($pedidoLocal->instituicaoResponsavelId())->toBe($instituto->id)
        ->and($pedidoColégio->instituicaoResponsavelId())->toBe($colegio->id)
        ->and($pedidoCertificado->instituicaoResponsavelId())->toBe($instituto->id);
});

it('emits an approved document and stores a generated pdf', function () {
    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $tutora = Instituicao::create([
        'nome' => 'Instituto Tutora',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $instituicao = Instituicao::create([
        'nome' => 'Instituição Emissora',
        'sigla' => 'IE',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $tutora->id,
        'instituicao_aprovadora_id' => $tutora->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Necessário para matrícula',
        'observacoes' => 'Pedido de teste',
        'status' => 'aprovado',
        'numero_processo' => '00001/2026',
        'data_solicitacao' => now(),
    ]);

    $response = $this->post('/dashboard/solicitacoes-documentos/'.$solicitacao->id.'/emitir', [
        'numero_registro_tutora' => 'REG/2026/0001',
    ]);

    $response->assertRedirect();

    expect($solicitacao->fresh()->status)->toBe('aprovado')
        ->and($solicitacao->fresh()->numero_registro_tutora)->toBe('REG/2026/0001')
        ->and($solicitacao->fresh()->data_emissao)->not->toBeNull();
});

it('does not emit documents approved only by the origin institution', function () {
    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $tutora = Instituicao::create([
        'nome' => 'Instituto Tutora',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $tutora->id,
        'instituicao_aprovadora_id' => $colegio->id,
        'instituicao_emissora_id' => $colegio->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Necessário para matrícula',
        'observacoes' => 'Pedido de teste',
        'status' => 'aprovado',
        'numero_processo' => '00001/2026',
        'data_solicitacao' => now(),
    ]);

    $user = User::factory()->create([
        'nome' => 'Emissor Teste',
        'email' => 'emissor-teste@example.com',
        'instituicao_id' => $colegio->id,
    ]);
    $user->assignRole(Role::firstOrCreate(['name' => 'Secretaria']));

    $this->actingAs($user);

    $response = $this->post('/dashboard/solicitacoes-documentos/'.$solicitacao->id.'/emitir', [
        'numero_registro_tutora' => 'REG/2026/0001',
    ]);

    $response->assertStatus(422);

    expect($solicitacao->fresh()->status)->toBe('aprovado')
        ->and($solicitacao->fresh()->data_emissao)->toBeNull();
});

it('routes document requests to the course tutora institution', function () {
    $role = Role::firstOrCreate(['name' => 'Aluno']);

    $originInstitution = Instituicao::create([
        'nome' => 'Colégio Origem',
        'sigla' => 'CO',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $tutoraInstitution = Instituicao::create([
        'nome' => 'Instituto Tutora',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $course = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 4,
        'descricao' => 'Curso de teste para solicitação de documentos',
        'status' => 1,
    ]);

    $institutionCourse = InstituicaoCurso::create([
        'curso_id' => $course->id,
        'instituicao_id' => $originInstitution->id,
        'duracao_anos' => 4,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $institutionCourse->id,
        'instituicao_tutora_id' => $tutoraInstitution->id,
    ]);

    $nivelEnsino = NivelEnsino::create([
        'nome' => 'Ensino Médio',
        'ordem' => 1,
        'activo' => true,
    ]);

    $classe = Classe::create([
        'nome' => '10ª Classe',
        'nivel_ensino' => 'Ensino Médio',
        'ordem' => 1,
    ]);

    $turno = Turno::create([
        'nome' => 'Manhã',
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $anoLectivo = AnoLectivo::create([
        'nome' => '2025/2026',
        'data_inicio' => now()->subYear(),
        'data_fim' => now(),
        'activo' => false,
        'estado' => 'encerrado',
    ]);

    $candidato = Candidato::create([
        'nome' => 'Aluno Teste',
        'bi' => '123456789AA000',
        'numero_estudante' => 'EST-'.Str::uuid(),
        'telefone' => '999000111',
        'email' => 'aluno-teste@example.com',
    ]);

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'aprovado',
    ]);

    $user = User::factory()->create([
        'nome' => 'Aluno Teste',
        'email' => 'user-aluno-teste@example.com',
        'instituicao_id' => $originInstitution->id,
    ]);
    $user->assignRole($role);

    Aluno::create([
        'user_id' => $user->id,
        'inscricao_id' => $inscricao->id,
        'situacao' => 'activo',
    ]);

    $this->actingAs($user);

    $response = $this->post('/dashboard/solicitacoes-documentos', [
        'tipo_documento' => 'declaracao',
        'motivo' => 'Necessário para matrícula',
        'observacoes' => 'Pedido automático de teste',
    ]);

    $response->assertRedirect('/dashboard/solicitacoes-documentos');

    $this->assertDatabaseHas('solicitacoes_documentos', [
        'aluno_id' => $user->aluno->id,
        'instituicao_origem_id' => $originInstitution->id,
        'instituicao_tutora_id' => $tutoraInstitution->id,
        'instituicao_aprovadora_id' => $originInstitution->id,
        'instituicao_emissora_id' => $originInstitution->id,
        'tipo_documento' => 'declaracao',
        'status' => 'pendente',
    ]);
});

it('colegio can encaminhar a document request to tutela', function () {
    $role = Role::firstOrCreate(['name' => 'Secretaria']);

    $colegio = Instituicao::create([
        'nome' => 'Colégio Origem',
        'sigla' => 'CO',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $tutora = Instituicao::create([
        'nome' => 'Instituto Tutora',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $tutora->id,
        'instituicao_aprovadora_id' => $colegio->id,
        'instituicao_emissora_id' => $colegio->id,
        'tipo_documento' => 'historico',
        'motivo' => 'Pedido do colégio',
        'status' => 'pendente',
        'numero_processo' => '00002/2026',
        'data_solicitacao' => now(),
    ]);

    $user = User::factory()->create([
        'nome' => 'Secretaria CO',
        'email' => 'secretaria-colegio@example.com',
        'instituicao_id' => $colegio->id,
    ]);
    $user->assignRole($role);

    $this->actingAs($user);

    $response = $this->post('/dashboard/solicitacoes-documentos/'.$solicitacao->id.'/enviar-tutela');

    $response->assertRedirect();

    expect($solicitacao->fresh()->status)->toBe('pendente')
        ->and($solicitacao->fresh()->instituicao_aprovadora_id)->toBe($colegio->id)
        ->and($solicitacao->fresh()->data_aprovacao)->not->toBeNull();
});

it('separates local and tutored requests on tutela page', function () {
    $instituicao = Instituicao::create([
        'nome' => 'Instituto Teste',
        'sigla' => 'IT',
        'tipo' => 'instituto',
        'status' => 1,
    ]);

    $colegio = Instituicao::create([
        'nome' => 'Colégio Teste',
        'sigla' => 'CT',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $instituicao->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_aprovadora_id' => $instituicao->id,
        'instituicao_emissora_id' => $instituicao->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Pedido local',
        'status' => 'pendente',
        'numero_processo' => '00003/2026',
        'data_solicitacao' => now(),
    ]);

    SolicitacaoDocumento::create([
        'aluno_id' => null,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $instituicao->id,
        'instituicao_aprovadora_id' => $colegio->id,
        'instituicao_emissora_id' => $colegio->id,
        'tipo_documento' => 'certificado',
        'motivo' => 'Pedido tutelado',
        'status' => 'pendente',
        'data_aprovacao' => now(),
        'numero_processo' => '00004/2026',
        'data_solicitacao' => now(),
    ]);

    $user = User::factory()->create([
        'nome' => 'Secretaria IT',
        'email' => 'secretaria-it@example.com',
        'instituicao_id' => $instituicao->id,
    ]);
    $user->assignRole(Role::firstOrCreate(['name' => 'Director']));

    $this->actingAs($user);

    $response = $this->get('/dashboard/solicitacoes-documentos/tutela');

    $response->assertOk();
    $response->assertInertia(
        fn (Assert $page) => $page
            ->component('dashboards/tutela/solicitacoes-documentos/index')
            ->has('solicitacoes_locais', 1)
            ->has('solicitacoes_tuteladas', 1)
    );
});
