import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

const NOTA_MAX = 20;
const NOTA_MIN = 10;

const COR_PAUTA = {
    finalizada: '#10b981',
    em_curso: '#f59e0b',
    por_iniciar: '#9ca3af',
};

function mediasPorDisciplina(turmas) {
    const mapa = {};
    turmas.forEach((t) => {
        const media = Number(t.media);
        if (t.media == null || Number.isNaN(media) || !t.disciplina) return;
        (mapa[t.disciplina] ??= []).push(media);
    });
    return Object.entries(mapa)
        .map(([disciplina, v]) => ({
            disciplina,
            media: Math.round((v.reduce((a, b) => a + b, 0) / v.length) * 10) / 10,
        }))
        .sort((a, b) => b.media - a.media);
}

function contarPautas(turmas, periodo) {
    const c = { finalizada: 0, em_curso: 0, por_iniciar: 0 };
    turmas.forEach((t) => {
        const pautas = Array.isArray(t.pautas) ? t.pautas : [];
        const estados = periodo ? [pautas[Number(periodo) - 1]] : pautas;
        estados.forEach((e) => {
            if (e === 'finalizada') c.finalizada++;
            else if (!e || e === 'por_iniciar') c.por_iniciar++;
            else c.em_curso++;
        });
    });
    return c;
}

export default function GraficosDesempenho({ turmas = [], periodo }) {
    const medias = mediasPorDisciplina(turmas);
    const pautas = contarPautas(turmas, periodo);
    const totalPautas = pautas.finalizada + pautas.em_curso + pautas.por_iniciar;

    const itensPautas = [
        { chave: 'finalizada', label: 'Finalizadas', valor: pautas.finalizada },
        { chave: 'em_curso', label: 'Em curso', valor: pautas.em_curso },
        { chave: 'por_iniciar', label: 'Por iniciar', valor: pautas.por_iniciar },
    ];
    const comValor = itensPautas.filter((i) => i.valor > 0);

    return (
        <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <Card className="gap-0 overflow-hidden pb-0">
                <CardHeader className="border-b pb-4">
                    <CardTitle>Média por disciplina</CardTitle>
                </CardHeader>
                <CardContent className="flex flex-col gap-4 p-4">
                    {medias.length === 0 && (
                        <p className="py-6 text-center text-xs text-muted-foreground">Sem notas lançadas.</p>
                    )}
                    {medias.map((m) => (
                        <div key={m.disciplina} className="flex flex-col gap-1.5">
                            <div className="flex items-center justify-between text-xs">
                                <span className="font-medium">{m.disciplina}</span>
                                <span className="text-muted-foreground">{m.media}</span>
                            </div>
                            <div className="relative h-3 w-full bg-muted">
                                <div
                                    className={m.media >= NOTA_MIN ? 'h-full bg-primary' : 'h-full bg-red-500'}
                                    style={{ width: `${Math.min((m.media / NOTA_MAX) * 100, 100)}%` }}
                                />
                                <div
                                    className="absolute inset-y-0 w-px bg-foreground/50"
                                    style={{ left: `${(NOTA_MIN / NOTA_MAX) * 100}%` }}
                                />
                            </div>
                        </div>
                    ))}
                </CardContent>
            </Card>

            <Card className="gap-0 overflow-hidden pb-0">
                <CardHeader className="border-b pb-4">
                    <CardTitle>Pautas por estado</CardTitle>
                </CardHeader>
                <CardContent className="p-4">
                    {totalPautas === 0 ? (
                        <p className="py-10 text-center text-xs text-muted-foreground">
                            Sem pautas para os filtros seleccionados.
                        </p>
                    ) : (
                        <div className="flex flex-col items-center gap-4">
                            <div className="relative h-64 w-full max-w-xs">
                                <ResponsiveContainer width="100%" height="100%">
                                    <PieChart>
                                        <Pie
                                            data={comValor}
                                            dataKey="valor"
                                            nameKey="label"
                                            innerRadius="55%"
                                            outerRadius="85%"
                                            paddingAngle={comValor.length > 1 ? 2 : 0}
                                            stroke="none"
                                        >
                                            {comValor.map((i) => (
                                                <Cell key={i.chave} fill={COR_PAUTA[i.chave]} />
                                            ))}
                                        </Pie>
                                        <Tooltip formatter={(v, nome) => [v, nome]} />
                                    </PieChart>
                                </ResponsiveContainer>
                                <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                                    <span className="text-2xl font-semibold">{totalPautas}</span>
                                    <span className="text-xs text-muted-foreground">pautas</span>
                                </div>
                            </div>

                            <ul className="flex w-full flex-wrap items-center justify-start gap-x-4 gap-y-2 text-xs text-muted-foreground">
                                {itensPautas.map((i) => (
                                    <li key={i.chave} className="flex items-center gap-1.5">
                                        <i className="size-2.5 rounded-sm" style={{ background: COR_PAUTA[i.chave] }} />
                                        <span>{i.label}</span>
                                        <span className="font-medium text-foreground">{i.valor}</span>
                                    </li>
                                ))}
                            </ul>
                        </div>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}