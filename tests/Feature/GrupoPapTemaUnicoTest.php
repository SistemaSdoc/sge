<?php

use App\Models\Central\AnoLectivo;
use App\Models\Tenant\Classe;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\Turma;
use App\Models\Tenant\Turno;
use App\Rules\EstudoCasoPapUnico;
use App\Rules\TemaPapUnico;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Validator;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    Artisan::call('migrate:fresh', [
        '--database' => 'sqlite',
        '--path' => database_path('migrations/tenant'),
        '--realpath' => true,
        '--no-interaction' => true,
    ]);
});

function createPapThemeUniquenessFixture(): array
{
    $instituicao = Instituicao::create([
        'nome' => 'Instituição PAP',
        'sigla' => 'IP',
        'tipo' => 'instituto',
        'email' => 'pap@example.test',
        'telefone' => '+244 900 000 000',
        'provincia' => 'Luanda',
        'endereco' => 'Rua PAP',
        'status' => 1,
        'descricao' => 'Instituição de teste',
    ]);
    $curso = Curso::create([
        'nome' => 'Curso PAP',
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
    $classe = Classe::create(['nome' => '13ª', 'ordem' => 13]);
    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
    ]);
    $anoLectivo = AnoLectivo::create([
        'nome' => '2026/2027',
        'data_inicio' => '2026-09-01',
        'data_fim' => '2027-07-31',
    ]);

    $criarTurno = function (string $nome) use ($cursoClasse, $anoLectivo): array {
        $turno = Turno::create(['nome' => $nome]);
        $cursoClasseTurno = CursoClasseTurno::create([
            'curso_classe_id' => $cursoClasse->id,
            'turno_id' => $turno->id,
        ]);
        $turma = Turma::create([
            'nome' => "Turma {$nome}",
            'max_alunos' => 30,
            'curso_classe_turno_id' => $cursoClasseTurno->id,
            'ano_lectivo_id' => $anoLectivo->id,
        ]);

        return [$cursoClasseTurno, $turma];
    };

    [$turnoManha, $turmaManha] = $criarTurno('Manhã');
    [$turnoTarde, $turmaTarde] = $criarTurno('Tarde');

    return compact('cursoTutelado', 'anoLectivo', 'turnoManha', 'turmaManha', 'turnoTarde', 'turmaTarde');
}

function criarGrupoPapComTema(Turma $turma, string $tema, string $estudoCaso): GrupoPap
{
    return GrupoPap::create([
        'turma_id' => $turma->id,
        'nome_grupo' => 'Grupo PAP',
        'tema_grupo' => $tema,
        'estudo_caso' => $estudoCaso,
    ]);
}

test('permite o mesmo tema noutro turno quando o estudo de caso é diferente e permite caso igual com tema diferente', function (): void {
    $fixture = createPapThemeUniquenessFixture();
    criarGrupoPapComTema($fixture['turmaManha'], 'Sistema escolar', 'Gestão dos arquivos físicos');

    $regraMesmoTemaCasoDiferente = new EstudoCasoPapUnico(
        (string) $fixture['cursoTutelado']->id,
        (string) $fixture['anoLectivo']->id,
        (string) $fixture['turnoTarde']->id,
        'Sistema escolar',
    );
    $regraTemaDiferenteMesmoCaso = new EstudoCasoPapUnico(
        (string) $fixture['cursoTutelado']->id,
        (string) $fixture['anoLectivo']->id,
        (string) $fixture['turnoTarde']->id,
        'Aplicação de gestão escolar',
    );
    $regraMesmoParOutroTurno = new TemaPapUnico(
        (string) $fixture['cursoTutelado']->id,
        (string) $fixture['anoLectivo']->id,
        (string) $fixture['turnoTarde']->id,
        null,
        'Gestão dos arquivos físicos',
    );

    $validacaoMesmoTemaCasoDiferente = Validator::make(
        ['estudo_caso' => 'Gestão de matrículas'],
        ['estudo_caso' => [$regraMesmoTemaCasoDiferente]],
    );
    $validacaoTemaDiferenteMesmoCaso = Validator::make(
        ['estudo_caso' => 'Gestão dos arquivos físicos'],
        ['estudo_caso' => [$regraTemaDiferenteMesmoCaso]],
    );
    $validacaoMesmoParOutroTurno = Validator::make(
        ['tema_grupo' => 'Sistema escolar'],
        ['tema_grupo' => [$regraMesmoParOutroTurno]],
    );

    expect($validacaoMesmoTemaCasoDiferente->passes())->toBeTrue()
        ->and($validacaoTemaDiferenteMesmoCaso->passes())->toBeTrue()
        ->and($validacaoMesmoParOutroTurno->fails())->toBeTrue();
});

test('impede repetir tema no mesmo turno e impede repetir o par tema/estudo de caso entre turnos', function (): void {
    $fixture = createPapThemeUniquenessFixture();
    criarGrupoPapComTema($fixture['turmaManha'], 'Sistema escolar', 'Gestão dos arquivos físicos');

    $regraTemaMesmoTurno = new TemaPapUnico(
        (string) $fixture['cursoTutelado']->id,
        (string) $fixture['anoLectivo']->id,
        (string) $fixture['turnoManha']->id,
    );
    $regraParDuplicadoOutroTurno = new EstudoCasoPapUnico(
        (string) $fixture['cursoTutelado']->id,
        (string) $fixture['anoLectivo']->id,
        (string) $fixture['turnoTarde']->id,
        'Sistema escolar',
    );
    $validacaoTemaMesmoTurno = Validator::make(
        ['tema_grupo' => ' SISTEMA   ESCOLAR '],
        ['tema_grupo' => [$regraTemaMesmoTurno]],
    );
    $validacaoParDuplicadoOutroTurno = Validator::make(
        ['estudo_caso' => 'GESTÃO DOS ARQUIVOS FÍSICOS'],
        ['estudo_caso' => [$regraParDuplicadoOutroTurno]],
    );

    expect($validacaoTemaMesmoTurno->fails())->toBeTrue()
        ->and($validacaoParDuplicadoOutroTurno->fails())->toBeTrue();
});
