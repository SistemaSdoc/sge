<?php

use App\Models\Aluno;
use App\Models\Candidato;
use App\Models\Inscricao;
use App\Models\TurmaAluno;
use Illuminate\Contracts\Console\Kernel;

// Carregar o autoloader e bootstrap do Laravel
require_once __DIR__.'/bootstrap/app.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Kernel::class);
$kernel->bootstrap();

// IDs da base de dados
$instituicaoId = '019ea82b-2478-703c-87f9-e616aad1694d'; // IMCL
$cursoClasseTurnoId = '019ea82b-26da-72cb-88b2-1048f5b3270c'; // Tarde, Classe 13
$turmaId = '019ea82b-299a-731e-8250-6f594f60ddc9'; // Turma ATI

// Nomes dos alunos
$nomes = ['Stelvio', 'Joaquim', 'Paulina', 'Mirian', 'Margarida'];

// Obter candidatos existentes
$candidatos = Candidato::whereIn('nome', $nomes)->get();

echo "=== Criando Alunos ===\n\n";

// Criar Inscrições e Alunos
foreach ($candidatos as $idx => $candidato) {
    // Criar inscrição se não existir
    $inscricao = Inscricao::where('candidato_id', $candidato->id)
        ->where('curso_classe_turno_id', $cursoClasseTurnoId)
        ->first();

    if (! $inscricao) {
        $inscricao = Inscricao::create([
            'candidato_id' => $candidato->id,
            'curso_classe_turno_id' => $cursoClasseTurnoId,
            'status' => 'aprovado',
        ]);
        echo "✓ Inscrição criada para {$candidato->nome}\n";
    } else {
        echo "✓ Inscrição já existe para {$candidato->nome}\n";
    }

    // Criar Aluno
    $aluno = Aluno::where('inscricao_id', $inscricao->id)->first();

    if (! $aluno) {
        $aluno = Aluno::create([
            'inscricao_id' => $inscricao->id,
            'user_id' => $candidato->user_id,
            'matricula' => 'ALU'.str_pad($idx + 1, 5, '0', STR_PAD_LEFT),
            'situacao' => 'activo',
        ]);
        echo "✓ Aluno criado: {$candidato->nome} (ID: {$aluno->id})\n";
    } else {
        echo "✓ Aluno já existe para {$candidato->nome}\n";
    }

    // Criar TurmaAluno
    $turmaAluno = TurmaAluno::where('turma_id', $turmaId)
        ->where('aluno_id', $aluno->id)
        ->first();

    if (! $turmaAluno) {
        $turmaAluno = TurmaAluno::create([
            'turma_id' => $turmaId,
            'aluno_id' => $aluno->id,
            'ano_lectivo' => date('Y'),
            'activo' => true,
            'situacao' => 'activo',
        ]);
        echo "✓ TurmaAluno criado: {$candidato->nome} na turma (ID: {$turmaAluno->id})\n";
    } else {
        echo "✓ TurmaAluno já existe para {$candidato->nome}\n";
    }

    echo "\n";
}

echo "\n=== Total de alunos na turma ===\n";
$total = TurmaAluno::where('turma_id', $turmaId)
    ->where('activo', true)
    ->where('situacao', 'activo')
    ->count();
echo "Total: $total alunos\n";
