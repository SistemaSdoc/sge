<?php

namespace App\Services;

use App\Models\Aluno;
use App\Models\AnoLectivo;
use App\Models\ConfirmacaoMatricula;
use App\Models\Turma;
use App\Models\TurmaAluno;
use App\Services\Core\RegraAcademicaService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ConfirmacaoMatriculaService
{
    public function __construct(private readonly RegraAcademicaService $regraAcademicaService) {}

    /**
     * Lista alunos que podem confirmar matrícula
     * (aprovados no ano actual com notas completas dos 3 períodos)
     */
    public function listarAlunosPorConfirmarMatricula(?Turma $turma = null, ?string $instituicaoId = null): LengthAwarePaginator
    {
        /**
         * Busca o ano lectivo actual em que a turma está inserida. Se a turma não for fornecida, busca o ano lectivo activo.
         */
        $anoAtual = $turma?->anoLectivo ?: AnoLectivo::ativo()->first();

        /**
         * Se não houver ano lectivo actual, retorna uma coleção vazia paginada.
         */
        if (! $anoAtual) {
            return collect()->paginate(10);
        }

        /**
         * Consulta a tabela pivot TurmaAluno para encontrar alunos que estão matriculados na turma actual e que têm notas completas dos 3 períodos.
         */
        return TurmaAluno::query()
            ->whereHas('turma', function ($q) use ($anoAtual, $turma, $instituicaoId) {
                /**
                 * Filtra por ano lectivo actual
                 */
                $q->where('ano_lectivo_id', $anoAtual->id);

                /**
                 * Se a instituição for fornecida, filtra por instituição
                 */
                if ($instituicaoId) {
                    $q->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado', function ($q2) use ($instituicaoId) {
                        $q2->where('instituicao_tutora_id', $instituicaoId);
                    });
                }

                /**
                 * Se a turma for fornecida, filtra por turma
                 */
                if ($turma) {
                    $q->where('id', $turma->id);
                }
            })

            /**
             * Filtra alunos que têm notas completas do período 1
             */
            ->whereHas('notas', function ($q) {
                $q->where('periodo', 1)->whereNotNull('media_trimestral');
            }, '>=', 1)

            /**
             * Filtra alunos que têm notas completas do período 2
             */
            ->whereHas('notas', function ($q) {
                $q->where('periodo', 2)->whereNotNull('media_trimestral');
            }, '>=', 1)

            /**
             * Filtra alunos que têm notas completas do período 3
             */
            ->whereHas('notas', function ($q) {
                $q->where('periodo', 3)->whereNotNull('media_trimestral');
            }, '>=', 1)

            /**
             * Filtra alunos que ainda não confirmaram matrícula para o próximo ano lectivo
             */
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'aluno.user:id,nome',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.classes',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            ])
            ->where('activo', true)
            ->orderBy('created_at')
            ->paginate(10)
            ->through(function (TurmaAluno $turmaAluno): array {
                $aluno = $turmaAluno->aluno;
                $turmaActual = $turmaAluno->turma;
                $classeActual = $turmaActual?->cursoClasseTurno?->cursoClasse?->classe?->nome;
                $ordemClasseActual = $turmaActual?->cursoClasseTurno?->cursoClasse?->classe?->ordem;

                $status = $this->regraAcademicaService->resolverSituacaoAcademica($turmaAluno)['situacao'];

                $classeProximoAno = match (true) {
                    in_array($status, ['transita', 'transita_com_deficiencia'], true) => $this->resolverProximaClasse($turmaActual, $ordemClasseActual),
                    default => $classeActual,
                };

                return [
                    'id' => $aluno?->id,
                    'nome' => $aluno?->inscricao?->candidato?->nome
                        ?? $aluno?->user?->nome
                        ?? 'Desconhecido',
                    'curso' => $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado?->instituicaoCurso?->curso?->nome,
                    'classe_actual' => $classeActual,
                    'classe_proximo_ano' => $classeProximoAno,
                    'turno' => $turmaActual?->cursoClasseTurno?->turno?->nome,
                    'turma' => $turmaActual?->nome,
                    'status' => $status,
                    'can' => [
                        'confirmar_matricula' => Auth::user()?->can('confirmar', $turmaAluno),
                    ],
                ];
            });
    }

    /**
     * Confirma a matrícula de um aluno no próximo ano lectivo.
     */
    public function confirmarMatricula(
        Aluno $aluno,
        Turma $turmaNova,
        Turma $turmaAtual,
    ): ConfirmacaoMatricula {
        /**
         * Cconsulta a tabela pivot TurmaAluno para encontrar a turma atual do aluno
         * e garantir que ele está matriculado na turma atual antes de confirmar a matrícula.
         */
        $turmaAtualAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaAtual->id)
            ->first();

        /**
         * Valida se o aluno está na turma atual
         */
        if (! $turmaAtualAluno) {
            throw new \Exception('Aluno não está nesta turma');
        }

        /**
         * Busca o ano lectivo em que o aluno está matriculado.
         */
        $anoAtual = $turmaAtual->anoLectivo;

        /**
         * Busca o próximo ano lectivo, com base na data de início do ano lectivo atual.
         *
         * O próximo ano lectivo deve começar após o término do ano lectivo atual.
         */
        $anoProximo = AnoLectivo::query()
            ->where('data_inicio', '>', $anoAtual->data_fim)
            ->orderBy('data_inicio')
            ->first();

        /**
         * Senão houver próximo ano lectivo, lança uma exceção.
         */
        if (! $anoProximo) {
            throw new \Exception('Próximo ano lectivo não existe');
        }

        /**
         * Verifica se o aluno já confirmou matrícula para o próximo ano lectivo.
         */
        $jaConfirmou = ConfirmacaoMatricula::where('aluno_id', $aluno->id)
            ->where('ano_lectivo_proximo_id', $anoProximo->id)
            ->where('status', 'confirmada')
            ->exists();

        /**
         * Se o aluno já confirmou matrícula, lança uma exceção para evitar duplicidade.
         *
         * Isso garante que um aluno não possa confirmar matrícula mais de uma vez para o mesmo ano lectivo.
         */
        if ($jaConfirmou) {
            throw new \Exception('Este aluno já confirmou matrícula para o próximo ano');
        }

        /**
         * Inicia uma transação para garantir que todas as operações sejam atômicas.
         *
         * Se qualquer operação falhar, todas as alterações serão revertidas.
         */
        return DB::transaction(function () use (
            $aluno,
            $anoAtual,
            $anoProximo,
            $turmaAtualAluno,
            $turmaNova,
        ) {
            /**
             * Cria um registro de confirmação de matrícula para o aluno, registrando a turma atual, a nova turma e os anos lectivos.
             *
             * O status da confirmação é definido como "confirmada" e a data de confirmação é registrada.
             * O ID do usuário que confirmou a matrícula é registrado para fins de auditoria.
             */
            $confirmacao = ConfirmacaoMatricula::create([
                'aluno_id' => $aluno->id,
                'ano_lectivo_atual_id' => $anoAtual->id,
                'ano_lectivo_proximo_id' => $anoProximo->id,
                'turma_atual_id' => $turmaAtualAluno->turma_id,
                'turma_nova_id' => $turmaNova->id,
                'status' => 'confirmada',
                'data_confirmacao' => now(),
                'confirmado_por' => Auth::id(),
            ]);

            /**
             * Cria um novo registro na tabela pivot TurmaAluno para associar o aluno à nova turma no próximo ano lectivo.
             */
            TurmaAluno::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turmaNova->id,
                'ano_lectivo_id' => $anoProximo->id,
                'activo' => true,
            ]);

            return $confirmacao;
        });
    }

    private function resolverProximaClasse(?Turma $turmaActual, ?int $ordemClasseActual): ?string
    {
        $cursoTutelado = $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado;
        if ($cursoTutelado === null || $ordemClasseActual === null) {
            return null;
        }

        return $cursoTutelado
            ->classes()
            ->where('ordem', $ordemClasseActual + 1)
            ->value('nome');
    }
}
