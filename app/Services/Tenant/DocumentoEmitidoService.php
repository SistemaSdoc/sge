<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Aluno;
use App\Models\Tenant\DocumentoEmitido;
use App\Models\Tenant\Turma;

class DocumentoEmitidoService
{
    /**
     * Regista uma emissão de documento. Chama isto logo a seguir a qualquer
     * geração bem-sucedida no DocumentosController (depois das validações
     * de "sem notas lançadas", para só contar o que foi mesmo gerado).
     *
     * Exemplo:
     *   $pdf = $this->certificadoService->gerarPdf($aluno, $turma);
     *   app(DocumentoEmitidoService::class)->registar(
     *       tipo: 'certificado',
     *       aluno: $aluno,
     *       turma: $turma,
     *   );
     */
    public function registar(
        string $tipo,
        ?Aluno $aluno = null,
        ?Turma $turma = null,
        ?string $subtipo = null,
        ?int $geradoPorId = null,
    ): DocumentoEmitido {
        return DocumentoEmitido::create([
            'tipo' => $tipo,
            'subtipo' => $subtipo,
            'aluno_id' => $aluno?->id,
            'turma_id' => $turma?->id,
            'gerado_por_id' => $geradoPorId ?? auth()->id(),
        ]);
    }

    /**
     * Devolve [['label' => ..., 'valor' => ...], ...] pronto para o gráfico,
     * agrupado por tipo, nos últimos N dias.
     */
    public function contarTodosTipos(int $dias = 30): array
    {
        $rotulos = [
            'declaracao' => 'Declarações',
            'certificado' => 'Certificados',
            'pauta' => 'Pautas',
        ];

        return DocumentoEmitido::where('created_at', '>=', now()->subDays($dias))
            ->get()
            ->groupBy('tipo')
            ->map(fn ($grupo, $tipo) => [
                'label' => $rotulos[$tipo] ?? ucfirst($tipo),
                'valor' => $grupo->count(),
            ])
            ->values()
            ->all();
    }
}