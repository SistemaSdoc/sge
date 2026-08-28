<?php

use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\Turma;

test('returns no institution when the group turma has no course shift', function () {
    $grupoPap = new GrupoPap;
    $grupoPap->setRelation(
        'turma',
        (new Turma)->setRelation('cursoClasseTurno', null)
    );

    expect($grupoPap->instituicao())->toBeNull();
});

test('returns the central tutor institution id from the course', function () {
    $grupoPap = new GrupoPap;
    $grupoPap->setRelation('turma',
        (new Turma)->setRelation('cursoClasseTurno',
            (new CursoClasseTurno)->setRelation('cursoClasse',
                (new CursoClasse)->setRelation('cursoTutelado',
                    new CursoTutelado(['instituicao_tutora_id' => 'tutor-id'])
                )
            )
        )
    );

    expect($grupoPap->instituicaoTutoraId())->toBe('tutor-id');
});
