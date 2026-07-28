<?php

use App\Models\AnoLectivo;
use App\Models\Classe;
use App\Models\Instituicao;
use App\Models\NivelEnsino;
use App\Models\RegraAvaliacao;
use App\Services\Core\RegraAcademica\RegraAplicavel;

it('resolves a class rule before a level rule and a general rule', function () {
    $instituicao = Instituicao::factory()->create();
    $anoLectivo = AnoLectivo::factory()->create([
        'data_inicio' => now()->startOfYear(),
        'data_fim' => now()->endOfYear(),
    ]);
    $nivel = NivelEnsino::create([
        'nome' => 'Secundário',
        'ordem' => 1,
        'activo' => true,
    ]);
    $classe = Classe::create([
        'nome' => '10ª Classe',
        'ordem' => 10,
    ]);

    $regraGeral = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => null,
        'nivel_ensino_id' => null,
        'nome' => 'Regra Geral',
        'media_minima_aprovacao' => 10,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $regraNivel = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => null,
        'nivel_ensino_id' => $nivel->id,
        'nome' => 'Regra de Nível',
        'media_minima_aprovacao' => 11,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $regraClasse = RegraAvaliacao::create([
        'instituicao_id' => $instituicao->id,
        'ano_lectivo_id' => $anoLectivo->id,
        'classe_id' => $classe->id,
        'nivel_ensino_id' => $nivel->id,
        'nome' => 'Regra de Classe',
        'media_minima_aprovacao' => 12,
        'frequencia_minima' => 75,
        'permite_recurso' => true,
        'activo' => true,
    ]);

    $resolver = app(RegraAplicavel::class);

    $regra = $resolver->resolve(
        instituicaoId: $instituicao->id,
        anoLectivoId: $anoLectivo->id,
        classeId: $classe->id,
        nivelEnsinoId: $nivel->id,
    );

    expect($regra)->not->toBeNull();
    expect($regra->id)->toBe($regraClasse->id);
});
