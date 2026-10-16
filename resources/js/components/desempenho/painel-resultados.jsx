import { usePage } from '@inertiajs/react';
import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const num = (v) => Number(v) || 0;
const pct = (v, t) => (t > 0 ? Math.round((v / t) * 100) : 0);

const COR = {
    aprovados: '#10b981',
    reprovados: '#ef4444',
    excluidos: '#f59e0b',
};

/**
 * Aceita o formato do service:
 * { total, aprovados, reprovados, excluidos, pendentes, fonte, por_turma: [...] }
 * venha como prop directa, aninhado (`resultados.resultados`) ou só nas props do Inertia.
 */
function normalizar(propResultados, pageProps) {
    let r = propResultados;

    if (r && typeof r === 'object' && r.resultados) r = r.resultados;

    if (!r || Object.keys(r).length === 0) {
        r =
            pageProps?.resultados ??
            pageProps?.desempenho?.resultados ??
            pageProps?.dados?.resultados ??
            pageProps?.data?.resultados ??
            {};
    }

    const aprovados = num(r.aprovados ?? r.aprovado);
    const reprovados = num(r.reprovados ?? r.reprovado);
    const excluidos = num(r.excluidos ?? r.excluido ?? r.excluidos_falta);

    const somaTres = aprovados + reprovados + excluidos;
    const total = num(r.total) > 0 ? Math.max(num(r.total), somaTres) : somaTres;

    const lista = Array.isArray(r.por_turma) ? r.por_turma : Object.values(r.por_turma ?? {});

    const por_turma = lista.map((t) => ({
        turma: t.turma ?? t.nome ?? '—',
        aprovados: num(t.aprovados ?? t.aprovado),
        reprovados: num(t.reprovados ?? t.reprovado),
        excluidos: num(t.excluidos ?? t.excluido ?? t.excluidos_falta),
    }));

    return {
        total,
        aprovados,
        reprovados,
        excluidos,
        por_turma,
        fonte: r.fonte,
        pendentes: num(r.pendentes),
        bruto: r,
    };
}

function Barra({ aprovados, reprovados, excluidos }) {
    const total = aprovados + reprovados + excluidos;
    if (total === 0) return <div className="h-3 w-full bg-muted" />;
    return (
        <div className="flex h-3 w-full overflow-hidden bg-muted">
            <div className="bg-emerald-500" style={{ width: `${pct(aprovados, total)}%` }} />
            <div className="bg-red-500" style={{ width: `${pct(reprovados, total)}%` }} />
            <div className="bg-amber-500" style={{ width: `${pct(excluidos, total)}%` }} />
        </div>
    );
}

function GraficoPie({ aprovados, reprovados, excluidos, total, fonte, pendentes }) {
    const dados = [
        { chave: 'aprovados', nome: 'Aprovados', valor: aprovados },
        { chave: 'reprovados', nome: 'Reprovados', valor: reprovados },
        { chave: 'excluidos', nome: 'Excluídos por faltas', valor: excluidos },
    ];
    const comValor = dados.filter((d) => d.valor > 0);

    return (
        <Card className="gap-0 overflow-hidden pb-0">
            <CardHeader className="border-b pb-4">
                <CardTitle>Resultados gerais</CardTitle>
            </CardHeader>
            <CardContent className="p-4">
                {total === 0 ? (
                    <p className="py-10 text-center text-xs text-muted-foreground">
                        Sem resultados para os filtros seleccionados.
                    </p>
                ) : (
                    <div className="flex flex-col items-center gap-4">
                        <div className="relative h-64 w-full max-w-xs">
                            <ResponsiveContainer width="100%" height="100%">
                                <PieChart>
                                    <Pie
                                        data={comValor}
                                        dataKey="valor"
                                        nameKey="nome"
                                        innerRadius="55%"
                                        outerRadius="85%"
                                        paddingAngle={comValor.length > 1 ? 2 : 0}
                                        stroke="none"
                                    >
                                        {comValor.map((d) => (
                                            <Cell key={d.chave} fill={COR[d.chave]} />
                                        ))}
                                    </Pie>
                                    <Tooltip formatter={(v, nome) => [`${v} (${pct(v, total)}%)`, nome]} />
                                </PieChart>
                            </ResponsiveContainer>
                            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                <span className="text-2xl font-semibold">{total}</span>
                                <span className="text-xs text-muted-foreground">alunos</span>
                            </div>
                        </div>

                        <ul className="flex w-full flex-wrap items-center justify-start gap-x-4 gap-y-2 text-xs text-muted-foreground">
                            {dados.map((d) => (
                                <li key={d.chave} className="flex items-center gap-1.5">
                                    <i className="size-2.5 rounded-sm" style={{ background: COR[d.chave] }} />
                                    <span>{d.nome}</span>
                                    <span className="font-medium text-foreground">{d.valor}</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                {(fonte === 'medias' || pendentes > 0) && (
                    <p className="mt-4 border-t pt-3 text-xs text-muted-foreground">
                        {fonte === 'medias' &&
                            'Calculado pelas médias das notas lançadas (média ≥ 10). Os excluídos por faltas contam os alunos com situação EEF.'}
                        {pendentes > 0 && ` ${pendentes} aluno(s) sem resultado definido.`}
                    </p>
                )}
            </CardContent>
        </Card>
    );
}

export default function PainelResultados({ resultados }) {
    const { props: pageProps } = usePage();
    const { total, aprovados, reprovados, excluidos, por_turma, fonte, pendentes, bruto } = normalizar(
        resultados,
        pageProps,
    );

    if (total === 0 && import.meta.env?.DEV) {
        // eslint-disable-next-line no-console
        console.warn('[PainelResultados] sem dados. Recebido:', { resultados, bruto, pageProps });
    }

    return (
        <>
            <GraficoPie
                aprovados={aprovados}
                reprovados={reprovados}
                excluidos={excluidos}
                total={total}
                fonte={fonte}
                pendentes={pendentes}
            />

        </>
    );
}