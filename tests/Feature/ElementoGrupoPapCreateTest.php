<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\ElementoGrupoPap;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

it('does not list or accept an aluno already assigned to another PAP group', function (): void {
    $instituicao = Instituicao::create([
        'nome' => 'Instituição Teste',
        'sigla' => 'IT',
        'tipo' => 'colegio',
        'email' => 'teste@escola.test',
        'telefone' => '+244 999 999 999',
        'provincia' => 'Luanda',
        'endereco' => 'Rua Teste',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);
    $curso = Curso::create([
        'nome' => 'Curso Teste',
        'descricao' => 'Curso de teste',
        'duracao_anos' => 1,
        'status' => 1,
    ]);
    $instituicaoCurso = InstituicaoCurso::create([
        'curso_id' => $curso->id,
        'instituicao_id' => $instituicao->id,
        'duracao_anos' => 1,
    ]);
    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => Classe::create(['nome' => '13A', 'ordem' => 13])->id,
    ]);
    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => Turno::create(['nome' => 'Manhã'])->id,
    ]);
    $turma = Turma::create([
        'nome' => 'Turma de destino',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);
    $turmaDoOutroGrupo = Turma::create([
        'nome' => 'Outra turma',
        'max_alunos' => 30,
        'curso_classe_turno_id' => $cursoClasseTurno->id,
    ]);
    $grupoPap = GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo de destino',
        'tema_grupo' => 'Tema',
        'status' => 'Em análise',
    ]);
    $outroGrupoPap = GrupoPap::create([
        'turma_id' => $turmaDoOutroGrupo->id,
        'nome_grupo' => 'Outro grupo',
        'tema_grupo' => 'Outro tema',
        'status' => 'Em análise',
    ]);

    $anoLectivoId = (string) Str::uuid();
    DB::table('ano_lectivos')->insert([
        'id' => $anoLectivoId,
        'nome' => '2026/2027',
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
        'activo' => false,
        'estado' => 'planeado',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $candidato = Candidato::create([
        'nome' => 'Aluno Já Integrado',
        'bi' => '020619207LA055',
        'numero_estudante' => 'EST-001',
    ]);
    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivoId,
        'status' => 'aprovado',
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'instituicao_id' => $instituicao->id,
        'matricula' => 'MAT-001',
        'situacao' => 'activo',
    ]);
    $turma->alunos()->attach($aluno->id, ['activo' => true]);
    ElementoGrupoPap::create([
        'grupo_pap_id' => $outroGrupoPap->id,
        'aluno_id' => $aluno->id,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);
    $user->givePermissionTo('elementogrupopap.create');
    $routeParameters = [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
        'turma' => $turma->id,
        'grupoPap' => $grupoPap->id,
    ];
    $createUrl = route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos.create', $routeParameters);

    $createResponse = $this->actingAs($user, 'tenant')->get($createUrl);

    $createResponse->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('tenant/cursos-tutelados/classes/turnos/turmas/pap/elementos/create')
        ->has('alunos', 0)
    );

    $storeResponse = $this->from($createUrl)->actingAs($user, 'tenant')->post(
        route('tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.elementos.store', $routeParameters),
        ['alunos' => [$aluno->id]],
    );

    $storeResponse->assertSessionHasErrors([
        'alunos.0' => 'Aluno Já Integrado já pertence a outro grupo PAP.',
    ]);
    $this->assertDatabaseMissing('elemento_grupo_pap', [
        'grupo_pap_id' => $grupoPap->id,
        'aluno_id' => $aluno->id,
    ]);
});
