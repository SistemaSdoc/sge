import { Head, router } from '@inertiajs/react';
import { BarChart3, X } from 'lucide-react';

import FiltrosDesempenho from '@/components/desempenho/filtros-desempenho';
import GraficoTurmas from '@/components/desempenho/grafico-turmas';
import GraficosDesempenho from '@/components/desempenho/graficos-desempenho';
import PainelPap from '@/components/desempenho/painel-pap';
import PainelResultados from '@/components/desempenho/painel-resultados';
import TabelaTurmas from '@/components/desempenho/tabela-turmas';
import { Button } from '@/components/ui/button';

export default function DesempenhoProfessor({
    resumo = {},
    resultados = {},
    turmas = [],
    pap = { total: 0, por_estado: {} },
    filtrosDisponiveis = { anosLectivos: [], periodos: [] },
    filtrosActivos = {},
    rotaIndex,
}) {
    const aplicar = (novos) => {
        const params = { ...filtrosActivos, ...novos };
        Object.keys(params).forEach((k) => !params[k] && delete params[k]);
        router.get(rotaIndex, params, { preserveState: true, preserveScroll: true, replace: true });
    };

    const temFiltros = filtrosActivos.ano_lectivo_id || filtrosActivos.periodo;

    return (
        <div className="mx-auto w-full max-w-7xl flex-col gap-6 p-4 md:p-6">
            <Head title="Meu Desempenho" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <div className="flex size-10 items-center justify-center bg-primary/10 text-primary">
                            <BarChart3 className="size-5" />
                        </div>
                        <div>
                            <h1 className="text-lg font-semibold">Meu Desempenho</h1>
                            <p className="text-xs text-muted-foreground">
                                Resumo das tuas turmas, disciplinas e pautas
                            </p>
                        </div>
                    </div>

                    {temFiltros && (
                        <Button variant="outline" size="sm" onClick={() => router.get(rotaIndex)}>
                            <X className="size-4" /> Limpar filtros
                        </Button>
                    )}
                </div>

                <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                    <GraficoTurmas turmas={turmas} totalTurmas={resumo.total_turmas ?? 0} />
                    <PainelResultados resultados={resultados} />
                </div>

                <GraficosDesempenho turmas={turmas} periodo={filtrosActivos.periodo} />

                <TabelaTurmas
                    turmas={turmas}
                    filtros={
                        <FiltrosDesempenho
                            disponiveis={filtrosDisponiveis}
                            activos={filtrosActivos}
                            onChnge={aplicar}
                        />
                    }
                />

                <PainelPap pap={pap} />
            </div>
        </div>
    );
}