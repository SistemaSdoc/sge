<?php

use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\CursoTuteladoProfessor;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\Professor;
use App\Models\Tenant\User;
use App\Notifications\Professor\ProfessorAdicionadoAoCursoNotification;

it('includes all assigned course roles in the email and database notification', function () {
    $instituicao = new Instituicao([
        'nome' => 'Instituição Teste',
        'tipo' => 'instituto',
    ]);
    $user = new User(['nome' => 'Professor Teste']);
    $user->setRelation('instituicao', $instituicao);

    $professor = new Professor;
    $professor->setRelation('user', $user);

    $vinculo = new CursoTuteladoProfessor([
        'tipo' => 'colaborador',
        'coordenador' => true,
        'opap' => true,
        'grupo_disciplinar' => 'coordenador',
    ]);

    $notification = new ProfessorAdicionadoAoCursoNotification(
        $professor,
        new CursoTutelado,
        $vinculo
    );

    $expectedPapeis = [
        'Professor colaborador',
        'Coordenador do curso',
        'OPAP',
        'Coordenador do Grupo Disciplinar',
    ];

    expect($notification->toMail($user)->viewData['papeis'])->toBe($expectedPapeis)
        ->and($notification->toArray($user)['papeis'])->toBe($expectedPapeis)
        ->and($notification->toArray($user)['mensagem'])->toContain(implode(', ', $expectedPapeis));
});
