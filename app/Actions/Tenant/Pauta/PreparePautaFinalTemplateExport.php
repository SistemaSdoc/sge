<?php

namespace App\Actions\Tenant\Pauta;

use App\Exports\PautaFinal\PautaFinalTemplateExcelExport;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pauta\Exports\Final\PautaFinalTemplateDataProvider;
use App\Services\Tenant\Pauta\Exports\PautaTemplateContextLoader;
use App\Services\Tenant\Pauta\Exports\PautaTemplateHeaderProvider;
use App\Services\Tenant\Pauta\ResolvePautaTemplatePath;

class PreparePautaFinalTemplateExport
{
    /** Injeta os serviços usados para preparar a pauta final. */
    public function __construct(
        private readonly ResolvePautaTemplatePath $resolvePautaTemplatePath,
        private readonly PautaTemplateContextLoader $contextLoader,
        private readonly PautaTemplateHeaderProvider $headerProvider,
        private readonly PautaFinalTemplateDataProvider $dataProvider,
    ) {}

    /** Prepara os dados e cria a exportação da pauta final. */
    public function handle(Turma $turma): PautaFinalTemplateExcelExport
    {
        $this->contextLoader->carregar($turma);

        $templatePath = $this->resolvePautaTemplatePath->resolveFinal($turma);

        abort_unless($templatePath !== null, 404, 'Não existe template final para esta classe e turno.');

        $dados = $this->dataProvider->preparar($turma);

        return new PautaFinalTemplateExcelExport(
            templatePath: $templatePath,
            header: $this->headerProvider->prepararFinal($turma),
            disciplines: $dados['disciplines'],
            students: $dados['students'],
        );
    }
}
