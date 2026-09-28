<?php

use App\Http\Resources\Tenant\CursoTutelado\CursoTuteladoResourceShow;
use App\Models\Tenant\Curso;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\InstituicaoCurso;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('o recurso do curso expõe a url das sugestões de temas PAP', function (): void {
    Storage::fake(config('filesystems.default'));

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
        'sugestoes_temas_pap_path' => 'pap/sugestoes-temas-pap.pdf',
    ]);

    $cursoTutelado->setRelation('instituicaoCurso', $instituicaoCurso->load(['curso', 'instituicao']));
    $cursoTutelado->setRelation('cursoClasses', collect());
    $cursoTutelado->setRelation('professores', collect());

    Storage::disk(config('filesystems.default'))->put('pap/sugestoes-temas-pap.pdf', '%PDF-1.7');

    $resource = (new CursoTuteladoResourceShow($cursoTutelado))->toArray(Request::create('/'));

    expect($resource)
        ->toHaveKey('sugestoes_temas_pap_url')
        ->and($resource['sugestoes_temas_pap_url'])->not->toBeNull();
});
