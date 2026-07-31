<?php

use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\Curso;
use App\Models\CursoClasse;
use App\Models\CursoClasseTurno;
use App\Models\CursoTutelado;
use App\Models\Instituicao;
use App\Models\InstituicaoCurso;
use App\Models\NivelEnsino;
use App\Models\Turno;
use App\Models\Turma;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

test('store creates a turma for the selected academic year', function () {
    Gate::before(fn () => true);

    $instituicao = Instituicao::create([
        'nome' => 'Escola Teste',
        'sigla' => 'ET',
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

    $classe = Classe::create([
        'nome' => '10A',
        'nivel_ensino' => 'secundario',
        'ordem' => 1,
    ]);

    $nivelEnsino = NivelEnsino::create([
        'nome' => 'Secundário',
        'ordem' => 1,
        'activo' => true,
    ]);

    $cursoClasse = CursoClasse::create([
        'curso_tutelado_id' => $cursoTutelado->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivelEnsino->id,
    ]);

    $turno = Turno::create(['nome' => 'Manhã']);

    $cursoClasseTurno = CursoClasseTurno::create([
        'curso_classe_id' => $cursoClasse->id,
        'turno_id' => $turno->id,
    ]);

    $anoAnterior = AnoLectivo::create([
        'nome' => '2024/2025',
        'data_inicio' => now()->subYear(),
        'data_fim' => now()->subDays(10),
        'activo' => false,
    ]);

    $anoAtual = AnoLectivo::create([
        'nome' => '2025/2026',
        'data_inicio' => now()->subMonths(2),
        'data_fim' => now()->addYear(),
        'activo' => true,
    ]);

    $user = User::factory()->create(['instituicao_id' => $instituicao->id]);

    $response = $this->actingAs($user)->post(route('turmas.store', [
        'instituicao' => $instituicao->id,
        'cursoTutelado' => $cursoTutelado->id,
        'cursoClasse' => $cursoClasse->id,
        'cursoClasseTurno' => $cursoClasseTurno->id,
    ]), [
        'nome' => 'Turma A',
        'max_alunos' => 30,
        'ano_lectivo_id' => $anoAtual->id,
    ]);

    $response->assertRedirect();

    $this->assertDatabaseHas('turmas', [
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'nome' => 'Turma A',
        'ano_lectivo_id' => $anoAtual->id,
    ]);

    $this->assertDatabaseMissing('turmas', [
        'curso_classe_turno_id' => $cursoClasseTurno->id,
        'nome' => 'Turma A',
        'ano_lectivo_id' => $anoAnterior->id,
    ]);
});
