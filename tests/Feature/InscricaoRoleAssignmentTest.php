<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turno;
use App\Models\Tenant\User;
use App\Services\Tenant\InscricaoService;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

uses()->group('inscricao');

test('approval flows keep existing and new student profiles linked', function () {
    Notification::fake();

    $roleProfessor = Role::create(['name' => 'Professor']);
    Role::create(['name' => 'Aluno']);

    $instituicao = Instituicao::create([
        'nome' => 'Instituição de Teste',
        'sigla' => 'TESTE',
        'tipo' => 'publica',
        'email' => 'teste@example.com',
        'telefone' => '912345678',
        'provincia' => 'Luanda',
        'endereco' => 'Rua de Teste',
        'status' => 1,
    ]);

    $curso = Curso::create([
        'nome' => 'Curso de Teste',
        'duracao_anos' => 3,
        'descricao' => 'Descrição de teste',
        'status' => 1,
    ]);

    $anoLectivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
    ]);

    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $instituicaoCurso = InstituicaoCurso::create([
        'instituicao_id' => $instituicao->id,
        'curso_id' => $curso->id,
        'duracao_anos' => 3,
    ]);

    $cursoTutelado = CursoTutelado::create([
        'instituicao_curso_id' => $instituicaoCurso->id,
        'instituicao_tutora_id' => $instituicao->id,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $professorUser = User::factory()->create([
        'bi' => '020619207LA055',
        'instituicao_id' => $instituicao->id,
    ]);
    $professorUser->assignRole($roleProfessor);

    Candidato::create([
        'nome' => 'Candidato Teste',
        'bi' => '020619207LA055',
        'numero_estudante' => 'ES-001',
        'telefone' => '923456789',
        'email' => 'candidato@example.com',
    ]);

    $candidato = Candidato::firstWhere('bi', '020619207LA055');

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'pendente',
    ]);
    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => null,
        'instituicao_id' => $instituicao->id,
    ]);

    $service = app(InscricaoService::class);
    $service->atualizarNotaTeste($inscricao, 14);

    $professorUser->refresh();
    $aluno->refresh();
    $candidato->refresh();

    expect($professorUser->hasRole('Professor'))->toBeTrue()
        ->and($professorUser->hasRole('Aluno'))->toBeTrue()
        ->and($aluno->user_id)->toBe($professorUser->id)
        ->and($candidato->user_id)->toBe($professorUser->id)
        ->and(Aluno::where('inscricao_id', $inscricao->id)->count())->toBe(1);

    $service->cancelar($inscricao);
    $aluno->update(['user_id' => null]);
    $service->reativar($inscricao);

    expect($aluno->refresh()->user_id)->toBe($professorUser->id);

    $instituicao->update(['tipo' => 'colegio']);
    $inscricaoColegio = $service->criar([
        'nome' => 'Candidato de Colégio',
        'bi' => '020619207LA056',
        'email' => 'colegio@example.com',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivo->id,
    ], $instituicao);
    $alunoColegio = Aluno::where('inscricao_id', $inscricaoColegio->id)->firstOrFail();

    expect($inscricaoColegio->status)->toBe('aprovado')
        ->and($alunoColegio->user_id)->not->toBeNull();

    User::factory()->create([
        'bi' => '020619207LA057',
        'email' => 'bi-account@example.com',
        'instituicao_id' => $instituicao->id,
    ]);
    User::factory()->create([
        'bi' => null,
        'email' => 'email-account@example.com',
        'instituicao_id' => $instituicao->id,
    ]);
    $candidatoConflituoso = Candidato::create([
        'nome' => 'Candidato com contas conflituosas',
        'bi' => '020619207LA057',
        'email' => 'email-account@example.com',
        'numero_estudante' => 'ES-003',
    ]);
    $inscricaoConflituosa = Inscricao::create([
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'candidato_id' => $candidatoConflituoso->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'status' => 'pendente',
    ]);

    expect(fn () => $service->atualizarNotaTeste($inscricaoConflituosa, 14))
        ->toThrow(ValidationException::class);
    expect($inscricaoConflituosa->refresh()->status)->toBe('pendente')
        ->and(Aluno::where('inscricao_id', $inscricaoConflituosa->id)->exists())->toBeFalse();
});
