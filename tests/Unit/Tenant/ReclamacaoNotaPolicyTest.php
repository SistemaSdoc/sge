<?php

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Nota;
use App\Models\Tenant\Professor;
use App\Models\Tenant\ReclamacaoNota;
use App\Models\Tenant\TurmaAluno;
use App\Models\Tenant\TurmaDisciplinaProfessor;
use App\Models\Tenant\User;
use App\Policies\Tenant\ReclamacaoNotaPolicy;

it('denies directing and student users from deciding a grade claim', function (): void {
    $policy = new ReclamacaoNotaPolicy;
    $reclamacao = new ReclamacaoNota;

    $aluno = Mockery::mock(User::class);
    $aluno->shouldReceive('hasRole')->with('Aluno')->andReturnTrue();
    $aluno->shouldReceive('hasAnyRole')->with(['Aluno', 'Director', 'Subdirector'])->andReturnTrue();

    $director = Mockery::mock(User::class);
    $director->shouldReceive('hasAnyRole')->with(['Aluno', 'Director', 'Subdirector'])->andReturnTrue();

    expect($policy->update($aluno, $reclamacao))->toBeFalse()
        ->and($policy->update($director, $reclamacao))->toBeFalse();
});

it('allows the discipline professor to decide an open claim', function (): void {
    $user = Mockery::mock(User::class);
    $professor = new Professor;
    $professor->id = 'professor-1';

    $professor->setRelation('user', $user);

    $tdp = new TurmaDisciplinaProfessor;
    $tdp->professor_id = 'professor-1';
    $tdp->setRelation('professor', $professor);

    $aluno = new Aluno;
    $aluno->setRelation('user', $user);

    $turmaAluno = new TurmaAluno;
    $turmaAluno->setRelation('aluno', $aluno);

    $nota = new Nota;
    $nota->setRelation('turmaAluno', $turmaAluno);
    $nota->setRelation('turmaDisciplinaProfessor', $tdp);

    $reclamacao = Mockery::mock(ReclamacaoNota::class)->makePartial();
    $reclamacao->shouldReceive('loadMissing')->andReturnSelf();
    $reclamacao->shouldReceive('estaAberta')->andReturnTrue();
    $reclamacao->solicitado_por = 'student-user-1';
    $reclamacao->status = 'pendente';
    $reclamacao->setRelation('nota', $nota);

    $user->shouldReceive('setAttribute')->with('id', 'professor-user-1')->andReturnSelf();
    $user->shouldReceive('getAttribute')->with('id')->andReturn('professor-user-1');
    $user->shouldReceive('getAttribute')->with('professor')->andReturn($professor);
    $user->shouldReceive('setRelation')->with('professor', $professor)->andReturnSelf();
    $user->id = 'professor-user-1';
    $user->setRelation('professor', $professor);

    $user->shouldReceive('hasAnyRole')->with(['Aluno', 'Director', 'Subdirector'])->andReturnFalse();
    $user->shouldReceive('hasAnyRole')->with(['Director', 'Subdirector'])->andReturnFalse();
    $user->shouldReceive('hasRole')->with('Aluno')->andReturnFalse();
    $user->shouldReceive('hasRole')->with('Professor')->andReturnTrue();
    $user->shouldReceive('hasRole')->with('Director')->andReturnFalse();
    $user->shouldReceive('hasRole')->with('Subdirector')->andReturnFalse();

    expect((new ReclamacaoNotaPolicy)->update($user, $reclamacao))->toBeTrue();
});
