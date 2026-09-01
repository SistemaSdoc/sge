<?php

use App\Models\Tenant\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turno;
use App\Services\Tenant\InscricaoService;

uses()->group('inscricao');

test('inscricao service uses the selected academic year when creating an inscription', function () {
    $anoLectivoActivo = AnoLectivo::create([
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->addYear(),
    ]);

    $anoLectivoAlternativo = AnoLectivo::create([
        'data_inicio' => now()->addYear(),
        'data_fim' => now()->addYears(2),
    ]);

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

    $service = app(InscricaoService::class);

    $inscricao = $service->criar([
        'nome' => 'Candidato Teste',
        'bi' => '020619207LA055',
        'numero_estudante' => 'ES-001',
        'telefone' => '923456789',
        'email' => 'candidato@example.com',
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'ano_lectivo_id' => $anoLectivoAlternativo->id,
    ]);

    expect($inscricao->ano_lectivo_id)->toBe($anoLectivoAlternativo->id)
        ->and($inscricao->candidato->bi)->toBe('020619207LA055');
});
