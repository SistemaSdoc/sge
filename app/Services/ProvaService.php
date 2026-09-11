<?php

namespace App\Services;

use App\Models\PrazoProva;
use App\Models\SubmissaoProva;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class ProvaService
{
    /**
     * Submete uma prova (cria nova versão ou substitui).
     *
     * @throws \Exception
     */
    public function submeterProva(
        PrazoProva $prazo,
        string $professorId,
        string $turmaId, 
        $arquivoProva,
        $arquivoChave,
        ?string $comentario = null
    ): SubmissaoProva {
        \Log::info('SERVICE - Iniciando submeterProva', [
            'prazo_id' => $prazo->id,
            'professor_id' => $professorId,
            'comentario' => $comentario,
        ]);

        if (!$prazo->isAberto()) {
            \Log::warning(' SERVICE - Prazo não está aberto', ['prazo_id' => $prazo->id, 'status' => $prazo->status]);
            throw new Exception('Este prazo não está aberto para submissões.');
        }

        $ultimaSubmissao = SubmissaoProva::where('prazo_prova_id', $prazo->id)
            ->where('professor_id', $professorId)
            ->where('turma_id', $turmaId) 
            ->latest('versao')
            ->first();

        $novaVersao = $ultimaSubmissao ? $ultimaSubmissao->versao + 1 : 1;
        \Log::info(' SERVICE - Versão calculada', ['versao' => $novaVersao, 'submissao_anterior' => $ultimaSubmissao ? $ultimaSubmissao->id : null]);

        //NOVA LÓGICA: permite reenvio se a última foi rejeitada, mesmo que permite_reenvio seja false
        if ($ultimaSubmissao) {
            $reenvioPermitido = $prazo->permite_reenvio || $ultimaSubmissao->estado === 'rejeitado';
            if (!$reenvioPermitido) {
                \Log::warning('SERVICE - Reenvio bloqueado', ['prazo_id' => $prazo->id]);
                throw new Exception('Reenvio não autorizado para este prazo.');
            }
        }

        try {
            \Log::info(' SERVICE - Armazenando arquivo da prova');
            $pathProva = $this->storeFile($arquivoProva, 'provas', $prazo->id, $professorId, $novaVersao);
            \Log::info('SERVICE - Prova armazenada', ['path' => $pathProva]);

            \Log::info('SERVICE - Armazenando arquivo da chave');
            $pathChave = $this->storeFile($arquivoChave, 'chaves', $prazo->id, $professorId, $novaVersao);
            \Log::info(' SERVICE - Chave armazenada', ['path' => $pathChave]);
        } catch (\Exception $e) {
            \Log::error(' SERVICE - Erro ao armazenar arquivos', ['error' => $e->getMessage()]);
            throw $e;
        }

        $dados = [
            'prazo_prova_id' => $prazo->id,
            'professor_id'   => $professorId,
            'disciplina_id'  => $prazo->disciplina_id,
            'classe_id'      => $prazo->classe_id,
            'turma_id'       => $turmaId, 
            'caminho_prova'  => $pathProva,
            'caminho_chave'  => $pathChave,
            'versao'         => $novaVersao,
            'comentario_professor' => $comentario,
            'estado'         => 'pendente',
            'data_submissao' => now(),
        ];

        \Log::info(' SERVICE - Tentando criar SubmissaoProva', $dados);

        try {
            $submissao = SubmissaoProva::create($dados);
            \Log::info(' SERVICE - Submissão criada', ['id' => $submissao->id]);
        } catch (\Exception $e) {
            \Log::error(' SERVICE - Falha ao criar SubmissaoProva', ['error' => $e->getMessage(), 'dados' => $dados]);
            throw $e;
        }

        if ($ultimaSubmissao) {
            $ultimaSubmissao->update(['estado' => 'substituido']);
            \Log::info(' SERVICE - Submissão anterior marcada como substituída', ['id' => $ultimaSubmissao->id]);
        }

        return $submissao;
    }

    /**
     * Avalia (aprova/rejeita) uma submissão.
     */
    public function avaliarSubmissao(SubmissaoProva $submissao, string $acao, ?string $parecer = null): SubmissaoProva
    {
        if (!in_array($acao, ['aprovar', 'rejeitar'])) {
            throw new Exception('Ação inválida. Use "aprovar" ou "rejeitar".');
        }

        $estado = $acao === 'aprovar' ? 'aprovado' : 'rejeitado';

        $submissao->update([
            'estado' => $estado,
            'parecer_diretor' => $parecer,
        ]);

        // event(new SubmissaoAvaliada($submissao));

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
            'status' => 'aberto', // reactiva caso esteja fechado
        ]);

        // event(new PrazoProrrogado($prazo));

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
        $nomeOriginal = $file->getClientOriginalName();
        $extensao = $file->getClientOriginalExtension();
        $nomeUnico = Str::uuid() . '.' . $extensao;

        $caminho = "provas/prazo_{$prazoId}/professor_{$professorId}/versao_{$versao}/{$tipo}/";

        Storage::disk('public')->putFileAs($caminho, $file, $nomeUnico);

        return $caminho . $nomeUnico;
    }

    /**
     * Obtém as submissões de um prazo com dados formatados para Inertia.
     */
    public function getSubmissoesParaIndex(PrazoProva $prazo, $user)
    {
        return $prazo->submissoes()
            ->with(['professor.user', 'disciplina', 'classe'])
            ->where('estado', '!=', 'substituido')
            ->get()
            ->map(function ($sub) use ($user) {
                return [
                    'id' => $sub->id,
                    'professor' => [
                        'id' => $sub->professor_id,
                        'nome' => $sub->professor && $sub->professor->user ? $sub->professor->user->nome : 'N/A',
                    ],
                    'disciplina' => $sub->disciplina ? $sub->disciplina->only(['id', 'nome', 'sigla']) : null,
                    'classe' => $sub->classe ? $sub->classe->only(['id', 'nome']) : null,
                    'versao' => $sub->versao,
                    'comentario' => $sub->comentario_professor,
                    'estado' => $sub->estado,
                    'estado_label' => $sub->estado_label,
                    'badge_class' => $sub->estado_badge_class,
                    'data_submissao' => $sub->data_submissao->format('d/m/Y H:i'),
                    'url_prova' => $sub->url_prova,
                    'url_chave' => $sub->url_chave,
                    'parecer' => $sub->parecer_diretor,
                    'can' => [
                        'avaliar' => $user->can('avaliar', $sub),
                    ],
                ];
            });
    }
}