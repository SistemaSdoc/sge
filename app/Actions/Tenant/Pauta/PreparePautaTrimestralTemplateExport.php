<?php

namespace App\Actions\Tenant\Pauta;

use App\Exports\PautaTrimestral\PautaTrimestralExcelExport;
use App\Models\Tenant\Turma;
use App\Services\Tenant\Pauta\Exports\PautaTemplateContextLoader;
use App\Services\Tenant\Pauta\Exports\PautaTemplateHeaderProvider;
use App\Services\Tenant\Pauta\Exports\Trimestral\PautaTrimestralTemplateDataProvider;
use App\Services\Tenant\Pauta\ResolvePautaTemplatePath;

class PreparePautaTrimestralTemplateExport
{
    /** Injeta os serviços necessários para preparar a pauta trimestral. */
    public function __construct(
        private readonly ResolvePautaTemplatePath $resolvePautaTemplatePath,
        private readonly PautaTemplateContextLoader $contextLoader,
        private readonly PautaTemplateHeaderProvider $headerProvider,
        private readonly PautaTrimestralTemplateDataProvider $dataProvider,
    ) {}

    /** Prepara os dados e cria a exportação da pauta trimestral. */
    public function handle(Turma $turma, int $periodo): PautaTrimestralExcelExport
    {
        $this->contextLoader->carregar($turma);

        $templatePath = $this->resolvePautaTemplatePath->resolveTrimestral($turma);

        abort_unless($templatePath !== null, 404, 'Não existe template trimestral para esta classe e turno.');

        $header = $this->headerProvider->prepararTrimestral($turma, $periodo);

        $disciplinas = $this->dataProvider->prepararDisciplinas($turma);

        $alunos = $this->dataProvider->prepararAlunos($turma, $periodo);

        return new PautaTrimestralExcelExport(
            templatePath: $templatePath,
            header: $header,
            disciplines: $disciplinas,
            students: $alunos,
        );
    }
}
