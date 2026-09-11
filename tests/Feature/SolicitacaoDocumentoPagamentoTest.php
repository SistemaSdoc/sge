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
use App\Notifications\PagamentoConfirmadoNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

it('secretaria can mark a solicitacao as paid and student receives notification', function () {
    $colegio = Instituicao::create([
        'nome' => 'Colégio Pagamento',
        'sigla' => 'CP',
        'tipo' => 'colegio',
        'status' => 1,
    ]);

    $alunoUser = User::factory()->create([
        'nome' => 'Aluno Pago',
        'email' => 'aluno-pago@example.com',
        'instituicao_id' => $colegio->id,
    ]);

    // Criar cadeia mínima para Inscricao/Aluno
    $curso = Curso::create(['nome' => 'Curso X', 'duracao_anos' => 3, 'descricao' => 'x', 'status' => 1]);
    $instituicaoCurso = InstituicaoCurso::create(['curso_id' => $curso->id, 'instituicao_id' => $colegio->id, 'duracao_anos' => 3]);
    $cursoTutelado = CursoTutelado::create(['instituicao_curso_id' => $instituicaoCurso->id, 'instituicao_tutora_id' => $colegio->id]);
    $nivel = NivelEnsino::create(['nome' => 'Ensino', 'ordem' => 1, 'activo' => true]);
    $classe = Classe::create(['nome' => '10ª', 'nivel_ensino' => 'Ensino', 'ordem' => 10]);
    $cursoClasse = CursoClasse::create(['curso_tutelado_id' => $cursoTutelado->id, 'classe_id' => $classe->id, 'nivel_ensino_id' => $nivel->id]);
    $turno = Turno::create(['nome' => 'Manhã']);
    $cursoClasseTurno = CursoClasseTurno::create(['turno_id' => $turno->id, 'curso_classe_id' => $cursoClasse->id]);
    $anoLectivo = AnoLectivo::create(['nome' => '2025/2026', 'data_inicio' => now()->subYear(), 'data_fim' => now(), 'activo' => true, 'estado' => 'em_curso']);
    $candidato = Candidato::create(['nome' => 'Cand', 'bi' => '000', 'numero_estudante' => Str::uuid(), 'telefone' => '000', 'email' => 'cand@example.com']);
    $inscricao = Inscricao::create(['curso_classe_turno_id' => $cursoClasseTurno->id, 'candidato_id' => $candidato->id, 'ano_lectivo_id' => $anoLectivo->id, 'status' => 'aprovado']);

    $aluno = Aluno::create([
        'user_id' => $alunoUser->id,
        'inscricao_id' => $inscricao->id,
        'situacao' => 'activo',
    ]);

    $solicitacao = SolicitacaoDocumento::create([
        'aluno_id' => $aluno->id,
        'instituicao_origem_id' => $colegio->id,
        'instituicao_tutora_id' => $colegio->id,
        'instituicao_aprovadora_id' => $colegio->id,
        'instituicao_emissora_id' => $colegio->id,
        'tipo_documento' => 'declaracao',
        'motivo' => 'Teste pagamento',
        'status' => 'pendente',
        'numero_processo' => '00010/2026',
        'data_solicitacao' => now(),
        'estado_pagamento' => 'pendente',
    ]);

    $role = Role::firstOrCreate(['name' => 'Secretaria']);

    $user = User::factory()->create([
        'nome' => 'Secretaria CO',
        'email' => 'secretaria-colegio@example.com',
        'instituicao_id' => $colegio->id,
    ]);
    $user->assignRole($role);

    $this->actingAs($user);

    $response = $this->patch('/dashboard/solicitacoes-documentos/'.$solicitacao->id.'/marcar-pago');

    $response->assertRedirect();

    expect($solicitacao->fresh()->estado_pagamento)->toBe('pago');

    $alunoUser->refresh();

    // Verifica diretamente na tabela de notifications para evitar diferenças de instância
    $notificationsCount = DB::table('notifications')
        ->where('notifiable_type', User::class)
        ->where('notifiable_id', $alunoUser->id)
        ->where('type', PagamentoConfirmadoNotification::class)
        ->count();

    expect($notificationsCount)->toBeGreaterThan(0);
});
