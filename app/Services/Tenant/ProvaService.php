<?php

namespace App\Services\Tenant;

use App\Models\Tenant\PrazoProva;
use App\Models\Tenant\SubmissaoProva;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProvaService
{
    /**
     * Submete uma prova (cria nova versão ou substitui).
     *
     * @param  bool  $permitirPrazoExtra  Autoriza submissão fora do prazo (justificativa aceite)
     * @throws Exception
     */
    public function submeterProva(
        PrazoProva $prazo,
        string $professorId,
        string $turmaId,
        $arquivoProva,
        $arquivoChave,
        ?string $comentario = null,
        bool $permitirPrazoExtra = false,
    ): SubmissaoProva {
        Log::info('SERVICE - Iniciando submeterProva', [
            'prazo_id'             => $prazo->id,
            'professor_id'         => $professorId,
            'turma_id'             => $turmaId,
            'comentario'           => $comentario,
            'permitir_prazo_extra' => $permitirPrazoExtra,
        ]);

        // ✅ Só bloqueia por prazo fechado se NÃO houver prazo extra autorizado
        if (! $permitirPrazoExtra && ! $prazo->isAberto()) {
            Log::warning('SERVICE - Prazo não está aberto', [
                'prazo_id' => $prazo->id,
                'status'   => $prazo->status,
            ]);

            throw new Exception('Este prazo não está aberto para submissões.');
        }

        $ultimaSubmissao = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professorId)
            ->where('turma_id', $turmaId)
            ->latest('versao')
            ->first();

        $novaVersao = $ultimaSubmissao ? $ultimaSubmissao->versao + 1 : 1;

        Log::info('SERVICE - Versão calculada', [
            'versao'              => $novaVersao,
            'submissao_anterior'  => $ultimaSubmissao?->id,
        ]);

        // Permite reenvio se a última foi rejeitada, mesmo que permite_reenvio seja false
        if ($ultimaSubmissao) {
            $reenvioPermitido = $prazo->permite_reenvio || $ultimaSubmissao->estado === 'rejeitado';

            if (! $reenvioPermitido) {
                Log::warning('SERVICE - Reenvio bloqueado', ['prazo_id' => $prazo->id]);
                throw new Exception('Reenvio não autorizado para este prazo.');
            }
        }

        $pathProva = null;
        $pathChave = null;

        try {
            Log::info('SERVICE - Armazenando arquivo da prova');
            $pathProva = $this->storeFile($arquivoProva, 'provas', $prazo->id, $professorId, $novaVersao);
            Log::info('SERVICE - Prova armazenada', ['path' => $pathProva]);

            Log::info('SERVICE - Armazenando arquivo da chave');
            $pathChave = $this->storeFile($arquivoChave, 'chaves', $prazo->id, $professorId, $novaVersao);
            Log::info('SERVICE - Chave armazenada', ['path' => $pathChave]);
        } catch (Exception $e) {
            if ($pathProva) {
                Storage::disk('local')->delete($pathProva);
            }

            Log::error('SERVICE - Erro ao armazenar arquivos', ['error' => $e->getMessage()]);
            throw $e;
        }

        $dados = [
            'prazo_prova_id'       => $prazo->id,
            'professor_id'         => $professorId,
            'disciplina_id'        => $prazo->disciplina_id,
            'classe_id'            => $prazo->classe_id,
            'turma_id'             => $turmaId,
            'caminho_prova'        => $pathProva,
            'caminho_chave'        => $pathChave,
            'versao'               => $novaVersao,
            'comentario_professor' => $comentario,
            'estado'               => 'pendente',
            'data_submissao'       => now(),
        ];

        Log::info('SERVICE - Tentando criar SubmissaoProva', $dados);

        try {
            $submissao = DB::transaction(function () use ($dados, $ultimaSubmissao): SubmissaoProva {
                $submissao = SubmissaoProva::create($dados);

                if ($ultimaSubmissao) {
                    $ultimaSubmissao->update(['estado' => 'substituido']);
                }

                return $submissao;
            });

            Log::info('SERVICE - Submissão criada', ['id' => $submissao->id]);
        } catch (Exception $e) {
            if ($pathProva) {
                Storage::disk('local')->delete($pathProva);
            }

            if ($pathChave) {
                Storage::disk('local')->delete($pathChave);
            }

            Log::error('SERVICE - Falha ao criar SubmissaoProva', [
                'error' => $e->getMessage(),
                'dados' => $dados,
            ]);

            throw $e;
        }

        return $submissao;
    }

    /**
     * Avalia (aprova/rejeita) uma submissão.
     */
/**
 * Avalia (aprova/rejeita) uma submissão.
 *
 * Ao rejeitar, concede automaticamente uma janela pessoal de reenvio
 * (prazo_reenvio_ate), limitada pela data_limite global do prazo caso
 * esta ainda esteja no futuro.
 *
 * @param  int|null  $diasReenvio  Sobrescreve config('provas.dias_reenvio_rejeicao')
 */
public function avaliarSubmissao(
    SubmissaoProva $submissao,
    string $acao,
    ?string $parecer = null,
    ?int $diasReenvio = null,
): SubmissaoProva {
    if (! in_array($acao, ['aprovar', 'rejeitar'])) {
        throw new Exception('Ação inválida. Use "aprovar" ou "rejeitar".');
    }

    $dados = [
        'estado'          => $acao === 'aprovar' ? 'aprovado' : 'rejeitado',
        'parecer_diretor' => $parecer,
    ];

    if ($acao === 'rejeitar') {
        $dias = $diasReenvio ?? (int) config('provas.dias_reenvio_rejeicao', 7);

        $limiteGlobal  = $submissao->prazo?->data_limite;
        $limiteReenvio = now()->addDays($dias);

        // Se o prazo global ainda está aberto, respeita o MAIOR entre os dois.
        // Caso contrário, usa a janela pessoal de reenvio.
        $dados['prazo_reenvio_ate'] = $limiteGlobal && $limiteGlobal->isAfter($limiteReenvio)
            ? $limiteGlobal
            : $limiteReenvio;

        Log::info('SERVICE - Rejeição com janela de reenvio', [
            'submissao_id'      => $submissao->id,
            'prazo_id'          => $submissao->prazo_prova_id,
            'prazo_global'      => $limiteGlobal?->toDateTimeString(),
            'prazo_reenvio_ate' => $dados['prazo_reenvio_ate']->toDateTimeString(),
            'dias_concedidos'   => $dias,
        ]);
    } else {
        // Ao aprovar, limpa qualquer janela de reenvio pendente
        $dados['prazo_reenvio_ate'] = null;
    }

    $submissao->update($dados);

    return $submissao;
}

    /**
     * Prorroga um prazo (altera data_limite).
     */
    public function prorrogarPrazo(PrazoProva $prazo, \DateTimeInterface $novaDataLimite): PrazoProva
    {
        if ($novaDataLimite <= $prazo->data_limite) {
            throw new Exception('A nova data deve ser posterior à data limite atual.');
        }

        $prazo->update([
            'data_limite' => $novaDataLimite,
            'status'      => 'aberto',
        ]);

        return $prazo;
    }

    /**
     * Fecha um prazo manualmente.
     */
    public function fecharPrazo(PrazoProva $prazo): PrazoProva
    {
        $prazo->update(['status' => 'fechado']);

        return $prazo;
    }

    /**
     * Armazena um ficheiro na estrutura de pastas.
     */
    private function storeFile($file, string $tipo, string $prazoId, string $professorId, int $versao): string
    {
        $extensao = $file->getClientOriginalExtension();
        $nomeUnico = Str::uuid().'.'.$extensao;

        $caminho = "provas/prazo_{$prazoId}/professor_{$professorId}/versao_{$versao}/{$tipo}/";

        Storage::disk('local')->putFileAs($caminho, $file, $nomeUnico);

        return $caminho.$nomeUnico;
    }

    /**
     * Obtém as submissões de um prazo com dados formatados para Inertia.
     */
    public function getSubmissoesParaIndex(PrazoProva $prazo, $user)
    {
        return $prazo->submissoes()
            ->with(['professor.user', 'disciplina', 'classe', 'turma'])
            ->where('estado', '!=', 'substituido')
            ->get()
            ->map(function ($sub) use ($user) {
                return [
                    'id' => $sub->id,
                    'professor' => [
                        'id'   => $sub->professor_id,
                        'nome' => $sub->professor?->user?->nome ?? 'N/A',
                    ],
                    'disciplina'    => $sub->disciplina ? $sub->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe'        => $sub->classe ? $sub->classe->only(['id', 'nome']) : null,
                    'turma'         => $sub->turma ? $sub->turma->only(['id', 'nome']) : null,
                    'versao'        => $sub->versao,
                    'comentario'    => $sub->comentario_professor,
                    'estado'        => $sub->estado,
                    'estado_label'  => $sub->estado_label,
                    'badge_class'   => $sub->estado_badge_class,
                    'data_submissao'=> $sub->data_submissao?->format('d/m/Y H:i'),
                    'url_prova'     => $sub->url_prova,
                    'url_chave'     => $sub->url_chave,
                    'parecer'       => $sub->parecer_diretor,
                    'can' => [
                        'avaliar' => $user->can('avaliar', $sub),
                    ],
                ];
            });
    }
}