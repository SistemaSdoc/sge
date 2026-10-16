import { Bar, BarChart, CartesianGrid, LabelList, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

/** Uma barra por turma; a mesma turma aparece em várias disciplinas, por isso conta-se uma vez. */
function alunosPorTurma(turmas) {
    const mapa = new Map();
    turmas.forEach((t) => {
        if (!t.turma || mapa.has(t.turma)) return;
        mapa.set(t.turma, Number(t.total_alunos) || 0);
    });
    return [...mapa].map(([turma, alunos]) => ({ turma, alunos }));
}

export default function GraficoTurmas({ turmas = [], totalTurmas = 0 }) {
    const dados = alunosPorTurma(turmas);

    return (
        <Card className="gap-0 overflow-hidden pb-0">
            <CardHeader className="flex flex-row items-center justify-between border-b pb-4">
                <CardTitle>Turmas</CardTitle>
                <span className="text-xs text-muted-foreground">
                    {totalTurmas} {totalTurmas === 1 ? 'turma' : 'turmas'} · alunos por turma
                </span>
            </CardHeader>
            <CardContent className="p-4">
                {dados.length === 0 ? (
                    <p className="py-10 text-center text-xs text-muted-foreground">
                        Nenhuma turma atribuída para os filtros seleccionados.
                    </p>
                ) : (
                    <div className="h-64 text-primary">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={dados} margin={{ top: 20, right: 8, left: -16, bottom: 0 }}>
                                <CartesianGrid vertical={false} stroke="currentColor" strokeOpacity={0.15} />
                                <XAxis dataKey="turma" fontSize={11} tickLine={false} />
                                <YAxis allowDecimals={false} fontSize={11} tickLine={false} axisLine={false} />
                                <Tooltip
                                    cursor={{ fill: 'rgba(0,0,0,0.05)' }}
                                    formatter={(v) => [v, 'Alunos']}
                                />
                                <Bar dataKey="alunos" name="Alunos" fill="currentColor" maxBarSize={40}>
                                    <LabelList dataKey="alunos" position="top" fontSize={11} />
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </div>
                )}
            </CardContent>
        </Card>
    );
}