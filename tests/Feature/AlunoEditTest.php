<?php

use App\Http\Controllers\Tenant\AlunoController;
use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;

beforeEach(function (): void {
    foreach (['alunos', 'inscricoes', 'candidatos', 'users'] as $table) {
        Schema::dropIfExists($table);
    }

    Schema::create('users', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('instituicao_id')->nullable();
        $table->string('nome');
        $table->string('email')->unique();
        $table->string('bi')->nullable()->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('candidatos', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('user_id')->nullable();
        $table->string('nome');
        $table->string('bi')->unique();
        $table->string('email')->nullable()->unique();
        $table->string('numero_estudante')->nullable()->unique();
        $table->timestamps();
        $table->softDeletes();
    });

    Schema::create('inscricoes', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('curso_classe_turno_id')->nullable();
        $table->uuid('candidato_id');
        $table->uuid('ano_lectivo_id')->nullable();
        $table->string('status')->default('aprovado');
        $table->timestamps();
    });

    Schema::create('alunos', function (Blueprint $table): void {
        $table->uuid('id')->primary();
        $table->uuid('inscricao_id');
        $table->uuid('user_id')->nullable();
        $table->uuid('instituicao_id')->nullable();
        $table->string('matricula')->nullable()->unique();
        $table->string('numero_processo')->nullable();
        $table->string('situacao')->default('activo');
        $table->timestamps();
        $table->softDeletes();
    });
});

it('updates the student personal profile without changing the matrícula', function () {
    $alunoUser = User::create([
        'instituicao_id' => 'instituicao-1',
        'nome' => 'Nome Antigo',
        'email' => 'aluno.antigo@example.test',
        'bi' => '1234567890',
    ]);

    $candidato = Candidato::create([
        'user_id' => $alunoUser->id,
        'nome' => 'Nome Antigo',
        'bi' => '1234567890',
        'email' => 'aluno.antigo@example.test',
        'numero_estudante' => 'INS-2026-TESTE',
    ]);

    $inscricao = Inscricao::create([
        'curso_classe_turno_id' => 'curso-classe-turno-1',
        'candidato_id' => $candidato->id,
        'ano_lectivo_id' => 'ano-lectivo-1',
        'status' => 'aprovado',
    ]);

    $aluno = Aluno::create([
        'inscricao_id' => $inscricao->id,
        'user_id' => $alunoUser->id,
        'instituicao_id' => 'instituicao-1',
        'matricula' => 'MAT-2026-0001',
        'numero_processo' => '00001',
        'situacao' => 'activo',
    ]);

    Gate::shouldReceive('authorize')->once()->with('update', $aluno);

    $response = app(AlunoController::class)->update(
        Request::create('/', 'PATCH', [
            'nome' => 'Ana Estudante',
            'bi' => '0987654321',
            'email' => 'ana.estudante@example.test',
            'matricula' => 'MAT-ALTERADA',
        ]),
        $aluno,
    );

    expect($response->getSession()->get('toast')['type'])->toBe('success');

    $this->assertDatabaseHas('candidatos', [
        'id' => $candidato->id,
        'nome' => 'Ana Estudante',
        'bi' => '0987654321',
        'email' => 'ana.estudante@example.test',
    ]);
    $this->assertDatabaseHas('users', [
        'id' => $alunoUser->id,
        'nome' => 'Ana Estudante',
        'bi' => '0987654321',
        'email' => 'ana.estudante@example.test',
    ]);
    $this->assertDatabaseHas('alunos', [
        'id' => $aluno->id,
        'matricula' => 'MAT-2026-0001',
    ]);
});
