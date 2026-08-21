<?php

declare(strict_types=1);

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\Candidato;
use App\Models\Tenant\Inscricao;
use App\Models\Tenant\TurmaAluno;
use Illuminate\Database\Seeder;

class CriarAlunosSeeder extends Seeder
{
    public function run(): void
    {
        $nomes = ['Stelvio', 'Joaquim', 'Paulina', 'Mirian', 'Margarida'];
        $cursoClasseTurnoId = '019ea82b-26da-72cb-88b2-1048f5b3270c'; // Tarde, Classe 13, Informática
        $turmaId = '019ea82b-299a-731e-8250-6f594f60ddc9'; // Turma ATI

        $candidatos = Candidato::whereIn('nome', $nomes)->get();

        foreach ($candidatos as $idx => $candidato) {
            // Criar Inscrição
            $inscricao = Inscricao::where('candidato_id', $candidato->id)
                ->where('curso_classe_turno_id', $cursoClasseTurnoId)
                ->first();

            if (! $inscricao) {
                $inscricao = Inscricao::create([
                    'candidato_id' => $candidato->id,
                    'curso_classe_turno_id' => $cursoClasseTurnoId,
                    'status' => 'aprovado',
                ]);
                echo "✓ Inscrição: {$candidato->nome}\n";
            }

            // Criar Aluno
            $aluno = Aluno::where('inscricao_id', $inscricao->id)->first();

            if (! $aluno) {
                $aluno = Aluno::create([
                    'inscricao_id' => $inscricao->id,
                    'user_id' => $candidato->user_id,
                    'matricula' => 'ALU'.str_pad((string) ($idx + 1), 5, '0', STR_PAD_LEFT),
                    'situacao' => 'activo',
                ]);
                echo "✓ Aluno: {$candidato->nome} (ID: {$aluno->id})\n";
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
                echo "✓ TurmaAluno: {$candidato->nome} na turma ATI\n";
            }
        }

        $total = TurmaAluno::where('turma_id', $turmaId)
            ->where('activo', true)
            ->where('situacao', 'activo')
            ->count();

        echo "\n📊 Total de alunos na turma: $total\n";
    }
}
