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
use Illuminate\Support\Facades\Log;

class ConfirmacaoMatriculaService
{
    public function __construct(private readonly RegraAcademicaService $regraAcademicaService)
    {
    }

    /**
     * Lista alunos que podem confirmar matrícula
     * (aprovados no ano actual com notas completas dos 3 períodos)
     */
    public function listarAlunosPorConfirmarMatricula(?Turma $turma = null, ?string $instituicaoId = null): LengthAwarePaginator
    {
        $anoAtual = $turma?->anoLectivo ?: AnoLectivo::ativo()->first();

        if (!$anoAtual) {
            return collect()->paginate(10);
        }

        // Buscar próximo ano ANTES da query principal
        $anoProximo = AnoLectivo::query()
            ->where('id', '!=', $anoAtual->id)
            ->where('data_inicio', '>', $anoAtual->data_inicio)
            ->orderBy('data_inicio')
            ->first();

        return TurmaAluno::query()
            ->whereHas('turma', function ($q) use ($anoAtual, $turma, $instituicaoId) {
                $q->where('ano_lectivo_id', $anoAtual->id);

                if ($instituicaoId) {
                    $q->whereHas('cursoClasseTurno.cursoClasse.cursoTutelado', function ($q2) use ($instituicaoId) {
                        $q2->where('instituicao_tutora_id', $instituicaoId);
                    });
                }

                if ($turma) {
                    $q->where('id', $turma->id);
                }
            })
            ->whereHas('notas', fn($q) => $q->where('periodo', 1)->whereNotNull('media_trimestral'), '>=', 1)
            ->whereHas('notas', fn($q) => $q->where('periodo', 2)->whereNotNull('media_trimestral'), '>=', 1)
            ->whereHas('notas', fn($q) => $q->where('periodo', 3)->whereNotNull('media_trimestral'), '>=', 1)
            ->where('activo', true)
            ->when($anoProximo, function ($q) use ($anoProximo) {
                $q->whereDoesntHave('aluno.confirmacoesMatricula', function ($q2) use ($anoProximo) {
                    $q2->where('status', 'confirmada')
                        ->where('ano_lectivo_proximo_id', $anoProximo->id);
                });
            })
            ->with([
                'aluno.inscricao.candidato:id,nome',
                'aluno.user:id,nome',
                'turma.cursoClasseTurno.turno',
                'turma.cursoClasseTurno.cursoClasse.classe',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.classes',
                'turma.cursoClasseTurno.cursoClasse.cursoTutelado.instituicaoCurso.curso',
                'notas.turmaDisciplinaProfessor.classeTurnoDisciplina.disciplina',
            ])
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
        Log::info('=== INICIANDO CONFIRMAÇÃO DE MATRÍCULA ===');
        Log::info('Aluno ID: ' . $aluno->id . ' (' . $aluno->nome . ')');
        Log::info('Turma Atual ID: ' . $turmaAtual->id . ' (' . $turmaAtual->nome . ')');
        Log::info('Turma Nova ID: ' . $turmaNova->id . ' (' . $turmaNova->nome . ')');

        // Validar se aluno está na turma atual
        $turmaAtualAluno = TurmaAluno::where('aluno_id', $aluno->id)
            ->where('turma_id', $turmaAtual->id)
            ->first();

        if (!$turmaAtualAluno) {
            Log::error('ERRO: Aluno não está nesta turma');
            throw new \Exception('Aluno não está nesta turma');
        }
        Log::info('✓ Aluno encontrado na turma atual');

        // Buscar ano lectivo atual
        $anoAtual = $turmaAtual->anoLectivo;
        Log::info('Ano Actual: ' . $anoAtual->nome . ' (ID: ' . $anoAtual->id . ')');
        Log::info('  - Data Início: ' . $anoAtual->data_inicio->format('Y-m-d H:i:s'));
        Log::info('  - Data Fim: ' . $anoAtual->data_fim->format('Y-m-d H:i:s'));
        Log::info('  - Activo: ' . ($anoAtual->activo ? 'SIM' : 'NÃO'));

        // Buscar próximo ano lectivo
        Log::info('Procurando próximo ano após: ' . $anoAtual->data_fim->format('Y-m-d H:i:s'));

        $anoProximo = AnoLectivo::query()
            ->where('id', '!=', $anoAtual->id)  // qualquer ano que não seja o actual
            ->where('data_inicio', '>', $anoAtual->data_inicio)  // mas depois do início do actual
            ->orderBy('data_inicio')
            ->first();

        if (!$anoProximo) {
            Log::error('ERRO: Próximo ano lectivo não existe');
            Log::info('Anos lectivos disponíveis:');
            AnoLectivo::orderBy('data_inicio')->each(function ($ano) {
                Log::info('  - ' . $ano->nome . ' (' . $ano->data_inicio->format('Y-m-d H:i:s') . ' até ' . $ano->data_fim->format('Y-m-d H:i:s') . ')');
            });
            throw new \Exception('Próximo ano lectivo não existe');
        }

        Log::info('✓ Próximo ano encontrado: ' . $anoProximo->nome . ' (ID: ' . $anoProximo->id . ')');
        Log::info('  - Data Início: ' . $anoProximo->data_inicio->format('Y-m-d H:i:s'));
        Log::info('  - Data Fim: ' . $anoProximo->data_fim->format('Y-m-d H:i:s'));

        // Validar se já confirmou
        $jaConfirmou = ConfirmacaoMatricula::where('aluno_id', $aluno->id)
            ->where('ano_lectivo_proximo_id', $anoProximo->id)
            ->where('status', 'confirmada')
            ->exists();

        if ($jaConfirmou) {
            Log::error('ERRO: Aluno já confirmou matrícula para este ano');
            throw new \Exception('Este aluno já confirmou matrícula para o próximo ano');
        }
        Log::info('✓ Aluno ainda não confirmou matrícula');

        // Transação
        Log::info('Iniciando transação...');

        return DB::transaction(function () use ($aluno, $anoAtual, $anoProximo, $turmaAtualAluno, $turmaNova, ) {
            Log::info('Criando registro de ConfirmacaoMatricula...');
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
            Log::info('✓ ConfirmacaoMatricula criada (ID: ' . $confirmacao->id . ')');

            Log::info('Criando TurmaAluno para o próximo ano...');
            $novaInscricao = TurmaAluno::create([
                'aluno_id' => $aluno->id,
                'turma_id' => $turmaNova->id,
                'ano_lectivo_id' => $anoProximo->id,
                'activo' => true,
            ]);
            Log::info('✓ TurmaAluno criada (ID: ' . $novaInscricao->id . ')');

            Log::info('=== CONFIRMAÇÃO DE MATRÍCULA CONCLUÍDA COM SUCESSO ===');

            return $confirmacao;
        });
    }

    private function resolverProximaClasse(?Turma $turmaActual, ?int $ordemClasseActual): ?string
    {
        Log::info('=== RESOLVENDO PRÓXIMA CLASSE ===');
        Log::info('Turma Actual: ' . $turmaActual?->nome);
        Log::info('Ordem Classe Actual: ' . $ordemClasseActual);

        $cursoTutelado = $turmaActual?->cursoClasseTurno?->cursoClasse?->cursoTutelado;

        if ($cursoTutelado === null) {
            Log::warning('AVISO: CursoTutelado é null');

            return null;
        }
        Log::info('✓ CursoTutelado encontrado: ' . $cursoTutelado->nome);

        if ($ordemClasseActual === null) {
            Log::warning('AVISO: Ordem Classe Actual é null');

            return null;
        }

        $proximaOrdem = $ordemClasseActual + 1;
        Log::info('Procurando classe com ordem: ' . $proximaOrdem);

        // Listar todas as classes do curso
        $todasClasses = $cursoTutelado->classes()->get();
        Log::info('Total de classes no curso: ' . $todasClasses->count());
        $todasClasses->each(function ($classe) {
            Log::info('  - Classe: ' . $classe->nome . ' (Ordem: ' . $classe->ordem . ')');
        });

        $proximaClasse = $cursoTutelado
            ->classes()
            ->where('ordem', $proximaOrdem)
            ->value('nome');

        if ($proximaClasse) {
            Log::info('✓ Próxima classe encontrada: ' . $proximaClasse);
        } else {
            Log::warning('AVISO: Nenhuma classe com ordem ' . $proximaOrdem . ' encontrada (possivelmente última classe)');
        }

        return $proximaClasse;
    }
}
