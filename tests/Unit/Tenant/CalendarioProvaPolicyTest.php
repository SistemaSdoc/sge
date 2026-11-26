<?php

use App\Models\Tenant\CalendarioProva;
use App\Models\Tenant\CursoClasse;
use App\Models\Tenant\CursoClasseTurno;
use App\Models\Tenant\CursoTutelado;
use App\Models\Tenant\InstituicaoCurso;
use App\Models\Tenant\User;
use App\Policies\Tenant\CalendarioProvaPolicy;

it('authorizes calendar actions through their Eloquent relations and keeps the institution boundary', function (): void {
    $calendarForInstitution = function (string $institutionId): CalendarioProva {
        $institutionCourse = new InstituicaoCurso;
        $institutionCourse->instituicao_id = $institutionId;

        $tutoredCourse = new CursoTutelado;
        $tutoredCourse->setRelation('instituicaoCurso', $institutionCourse);

        $courseClass = new CursoClasse;
        $courseClass->setRelation('cursoTutelado', $tutoredCourse);

        $courseClassShift = new CursoClasseTurno;
        $courseClassShift->setRelation('cursoClasse', $courseClass);

        $calendar = new CalendarioProva;
        $calendar->setRelation('cursoClasseTurno', $courseClassShift);

        return $calendar;
    };

    /** @var User $user */
    $user = Mockery::mock(User::class)->makePartial();
    $user->shouldReceive('can')->andReturnTrue();
    $user->instituicao_id = 'institution-1';

    $policy = new CalendarioProvaPolicy;
    $ownCalendar = $calendarForInstitution('institution-1');
    $otherInstitutionCalendar = $calendarForInstitution('institution-2');

    expect($policy->view($user, $ownCalendar))->toBeTrue()
        ->and($policy->update($user, $ownCalendar))->toBeTrue()
        ->and($policy->delete($user, $ownCalendar))->toBeTrue()
        ->and($policy->toggleAtivo($user, $ownCalendar))->toBeTrue()
        ->and($policy->view($user, $otherInstitutionCalendar))->toBeFalse()
        ->and($policy->update($user, $otherInstitutionCalendar))->toBeFalse()
        ->and($policy->delete($user, $otherInstitutionCalendar))->toBeFalse()
        ->and($policy->toggleAtivo($user, $otherInstitutionCalendar))->toBeFalse();
});
