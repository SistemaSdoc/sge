<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\Candidato;
use App\Models\Inscricao;
use App\Models\Instituicao;
use App\Models\User;
use App\Services\AnoLectivo\AnoLectivoResolverService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Spatie\Permission\Models\Role;

class InscricaoService
{
    public function __construct(private readonly AnoLectivoResolverService $anoLectivoResolverService) {}

    /**
     * Cria um registro em inscrições para o candidato.
     *
     * Para instituições do tipo "colegio" a inscrição é imediatamente aprovada
     * sem necessidade de nota de teste.
     * Para instituições do tipo "instituto" o estado é calculado com base na nota.
     */
    public function criar(array $dados, ?Instituicao $instituicao = null): Inscricao
    {
        return DB::transaction(function () use ($dados) {
            $candidato = Candidato::create([
                'nome' => $dados['nome'],
                'bi' => $dados['bi'],
                'numero_estudante' => $dados['numero_estudante'] ?? null,
                'telefone' => $dados['telefone'] ?? null,
                'email' => $dados['email'],
                'morada' => $dados['morada'] ?? null,
                'genero' => $dados['genero'] ?? null,
                'nacionalidade' => $dados['nacionalidade'] ?? null,
                'naturalidade' => $dados['naturalidade'] ?? null,
                'filiacao' => $dados['filiacao'] ?? null,
                'data_nascimento' => $dados['data_nascimento'],
            ]);

            $anoLectivoId = $dados['ano_lectivo_id'] ?? $this->anoLectivoResolverService->obterAnoLectivoDefault();

            if (! $anoLectivoId) {
                throw new InvalidArgumentException('Nenhum ano lectivo activo encontrado.');
            }

            $hasNota = isset($dados['nota_teste']) && $dados['nota_teste'] !== '' && $dados['nota_teste'] !== null;
            $nota = $hasNota ? (float) $dados['nota_teste'] : null;
            $status = $hasNota ? ($nota >= 10 ? 'aprovado' : 'reprovado') : 'pendente';

            $inscricao = Inscricao::create([
                'candidato_id' => $candidato->id,
                'curso_classe_turno_id' => $dados['curso_classe_turno_id'],
                'ano_lectivo_id' => $anoLectivoId,
                'status' => $status,
                'nota_teste' => $nota,
            ]);

            if ($status === 'aprovado') {
                $this->criarAlunoSeNecessario($inscricao, $dados['turma_id'] ?? null);
            }

            return $inscricao;
        });
    }

    /**
     * Método para actualizar a nota do teste de um candidato a uma inscrição.
     */
    public function atualizarNotaTeste(Inscricao $inscricao, float $nota): void
    {
        $inscricao->load([
            'candidato',
            'cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso',
        ]);

        DB::transaction(function () use ($inscricao, $nota) {
            $statusAnterior = $inscricao->status;
            $statusCalculado = $nota >= 10 ? 'aprovado' : 'reprovado';

            Log::info('Inscrição — atualizar nota', [
                'inscricao_id' => $inscricao->id,
                'status_anterior' => $statusAnterior,
                'status_novo' => $statusCalculado,
                'nota_teste' => $nota,
            ]);

            $inscricao->update([
                'nota_teste' => $nota,
                'status' => $statusCalculado,
            ]);

            $deveCriarAluno = $statusCalculado === 'aprovado'
                && $statusAnterior !== 'aprovado';

            if ($deveCriarAluno) {
                $this->criarAlunoSeNecessario($inscricao);
            }
        });
    }

    /**
     * Cria um aluno associado a uma inscrição, caso ainda não exista.
     */
    private function criarAlunoSeNecessario(Inscricao $inscricao, ?string $turmaId = null): void
    {
        if (! $inscricao->candidato?->bi) {
            throw new InvalidArgumentException('O candidato não tem BI registado.');
        }

        if (Aluno::where('inscricao_id', $inscricao->id)->exists()) {
            return;
        }

        $instituicaoId = $inscricao->cursoClasseTurno
            ?->cursoClasse
            ?->cursoTutelado
            ?->instituicaoCurso
            ?->instituicao_id;

        if (! $instituicaoId) {
            throw new InvalidArgumentException('Não foi possível determinar a instituição da inscrição.');
        }

        $user = User::firstOrCreate(
            ['bi' => $inscricao->candidato->bi],
            [
                'nome' => $inscricao->candidato->nome,
                'email' => $inscricao->candidato->email,
                'telefone' => $inscricao->candidato->telefone,
                'instituicao_id' => $instituicaoId,
                'password' => Hash::make('12345678'),
            ]
        );

        $role = Role::where('name', 'Aluno')->firstOrFail();
        $user->assignRole($role);

        $aluno = Aluno::create([
            'user_id' => $user->id,
            'inscricao_id' => $inscricao->id,
            'ano_lectivo_id' => $inscricao->ano_lectivo_id,
            'matricula' => $this->gerarMatriculaUnica(),
        ]);

        if ($turmaId) {
            $aluno->turmas()->syncWithoutDetaching([
                $turmaId => [
                    'activo' => true,
                    'situacao' => 'activo',
                    'ano_lectivo_id' => $inscricao->ano_lectivo_id,
                ],
            ]);
        }
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
