<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Inscricao;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InscricaoService
{
    public function atualizarNotaTeste(Inscricao $inscricao, float $nota): void
    {
        DB::transaction(function () use ($inscricao, $nota) {
            $statusAnterior = $inscricao->status;
            $statusCalculado = $nota >= 10 ? 'aprovado' : 'reprovado';

            Log::info('INSCRICAO UPDATE', [
                'inscricao_id' => $inscricao->id,
                'status_anterior' => $statusAnterior,
                'nota_teste' => $nota,
                'candidato_email' => $inscricao->candidato?->email,
                'candidato_nome' => $inscricao->candidato?->nome,
                'instituicao_id' => $inscricao->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->instituicao_id,
            ]);

            $inscricao->update([
                'nota_teste' => $nota,
                'status' => $statusCalculado,
            ]);

            Log::info('STATUS CALCULADO', [
                'statusCalculado' => $statusCalculado,
                'statusAnterior' => $statusAnterior,
                'vai_criar_aluno' => $statusCalculado === 'aprovado' && $statusAnterior !== 'aprovado',
            ]);

            if ($statusCalculado === 'aprovado' && $statusAnterior !== 'aprovado') {
                $this->criarAlunoSeNecessario($inscricao);
            }
        });
    }

    private function criarAlunoSeNecessario(Inscricao $inscricao): void
    {
        if (! $inscricao->candidato?->email) {
            throw new \InvalidArgumentException('O candidato não tem email registado.');
        }

        if (Aluno::where('inscricao_id', $inscricao->id)->exists()) {
            return;
        }

        $user = User::firstOrCreate(
            ['email' => $inscricao->candidato->email],
            [
                'nome' => $inscricao->candidato->nome,
                'password' => Hash::make('123456'),
                'telefone' => $inscricao->candidato->telefone,
                'bi' => $inscricao->candidato->bi,
                'instituicao_id' => $inscricao->cursoClasseTurno
                    ->cursoClasse
                    ->cursoTutelado
                    ->instituicaoCurso
                    ->instituicao_id,
            ]
        );

        $roleAluno = Role::where('nome', 'Aluno')->first();
        if ($roleAluno) {
            $user->roles()->syncWithoutDetaching([$roleAluno->id]);
        }

        $matricula = $this->gerarMatriculaUnica();

        try {
            Aluno::create([
                'user_id' => $user->id,
                'inscricao_id' => $inscricao->id,
                'matricula' => $matricula,
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() == 23000) {
                // Retry with new matricula
                $matricula = $this->gerarMatriculaUnica();
                Aluno::create([
                    'user_id' => $user->id,
                    'inscricao_id' => $inscricao->id,
                    'matricula' => $matricula,
                ]);
            } else {
                throw $e;
            }
        }
    }

    private function gerarMatriculaUnica(): string
    {
        $ano = now()->year;

        // Com UUID v7 ordenável, poderíamos usar orderByDesc('id'), mas MAX é mais seguro
        $maxNumero = Aluno::where('matricula', 'like', "MAT-$ano-%")
            ->selectRaw('MAX(CAST(SUBSTRING(matricula, -4) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        $novoNumero = str_pad($maxNumero + 1, 4, '0', STR_PAD_LEFT);

        return "MAT-$ano-$novoNumero";
    }
}
