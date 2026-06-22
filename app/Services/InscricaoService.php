<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Candidato;
use App\Models\Inscricao;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;

class InscricaoService
{
    public function criar(array $dados): Inscricao
    {
        return DB::transaction(function () use ($dados) {
            $candidato = Candidato::create([
                'nome'             => $dados['nome'],
                'bi'               => $dados['bi'],
                'numero_estudante' => $dados['numero_estudante'],
                'telefone'         => $dados['telefone'] ?? null,
                'email'            => $dados['email'],
            ]);

            return Inscricao::create([
                'candidato_id'          => $candidato->id,
                'curso_classe_turno_id' => $dados['curso_classe_turno_id'],
                'status'                => 'pendente',
            ]);
        });
    }

    public function atualizarNotaTeste(Inscricao $inscricao, float $nota): void
    {
        DB::transaction(function () use ($inscricao, $nota) {
            $statusAnterior  = $inscricao->status;
            $statusCalculado = $nota >= 10 ? 'aprovado' : 'reprovado';

            Log::info('Inscrição — atualizar nota', [
                'inscricao_id'    => $inscricao->id,
                'status_anterior' => $statusAnterior,
                'status_novo'     => $statusCalculado,
                'nota_teste'      => $nota,
            ]);

            $inscricao->update([
                'nota_teste' => $nota,
                'status'     => $statusCalculado,
            ]);

            $devecriarAluno = $statusCalculado === 'aprovado'
                && $statusAnterior !== 'aprovado';

            if ($devecriarAluno) {
                $this->criarAlunoSeNecessario($inscricao);
            }
        });
    }

    // ─── Privados ─────────────────────────────────────────────────────────────

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
                'nome'           => $inscricao->candidato->nome,
                'password'       => Hash::make('123456'),
                'telefone'       => $inscricao->candidato->telefone,
                'bi'             => $inscricao->candidato->bi,
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

        Aluno::create([
            'user_id'      => $user->id,
            'inscricao_id' => $inscricao->id,
            'matricula'    => $this->gerarMatriculaUnica(),
        ]);
    }

    private function gerarMatriculaUnica(): string
    {
        $ano = now()->year;

        // lockForUpdate garante que dois requests simultâneos
        // nunca lêem o mesmo MAX — elimina a race condition
        $max = Aluno::where('matricula', 'like', "MAT-{$ano}-%")
            ->lockForUpdate()
            ->selectRaw('MAX(CAST(SUBSTRING_INDEX(matricula, "-", -1) AS UNSIGNED)) as max_num')
            ->value('max_num') ?? 0;

        return sprintf('MAT-%d-%04d', $ano, $max + 1);
    }
}