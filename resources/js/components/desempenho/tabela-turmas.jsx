import { GraduationCap } from 'lucide-react';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import BadgePauta from './badge-pauta';

export default function TabelaTurmas({ turmas, filtros }) {
    return (
        <Card className="gap-0 overflow-hidden pb-0">
            <CardHeader className="border-b pb-4">
                <CardTitle>Desempenho por turma e disciplina</CardTitle>
            </CardHeader>

            {filtros && (
                <div className="flex flex-wrap items-center justify-end gap-2 border-b bg-muted/30 px-4 py-3">
                    {filtros}
                </div>
            )}

            <CardContent className="px-0">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/50 hover:bg-muted/50">
                            <TableHead className="pl-4">Turma</TableHead>
                            <TableHead>Disciplina</TableHead>
                            <TableHead className="text-center">Alunos</TableHead>
                            <TableHead className="text-center">1º Trimestre</TableHead>
                            <TableHead className="text-center">2º Trimestre</TableHead>
                            <TableHead className="pr-4 text-center">3º Trimestre</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {turmas.length === 0 && (
                            <TableRow>
                                <TableCell colSpan={6} className="py-10 text-center text-muted-foreground">
                                    <GraduationCap className="mx-auto mb-2 size-8 opacity-40" />
                                    Nenhuma turma atribuída para os filtros seleccionados.
                                </TableCell>
                            </TableRow>
                        )}
                        {turmas.map((t, i) => (
                            <TableRow key={`${t.turma}-${t.disciplina}-${i}`}>
                                <TableCell className="pl-4 font-medium">{t.turma ?? '—'}</TableCell>
                                <TableCell>{t.disciplina ?? '—'}</TableCell>
                                <TableCell className="text-center">{t.total_alunos}</TableCell>
                                {[0, 1, 2].map((idx) => (
                                    <TableCell key={idx} className={`text-center ${idx === 2 ? 'pr-4' : ''}`}>
                                        <BadgePauta estado={t.pautas?.[idx] ?? 'por_iniciar'} />
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    );
}