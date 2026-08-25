import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

const TITULOS = {
  pagos: 'Alunos com propina em dia',
  nao_pagos: 'Alunos com propina em atraso',
  pendentes: 'Alunos pendentes (1 ou mais meses em atraso)',
};

export default function AlunosPorStatusPropina({
  statusFiltro,
  alunosPorStatus,
}) {
  const classes = Object.keys(alunosPorStatus ?? {});

  if (classes.length === 0) {
    return (
      <Card>
        <CardContent className="p-6 text-sm text-muted-foreground">
          Nenhum aluno encontrado para este filtro.
        </CardContent>
      </Card>
    );
  }

  return (
    <div className="space-y-8">
      <h2 className="text-lg font-semibold">
        {TITULOS[statusFiltro] ?? 'Alunos'}
      </h2>

      {classes.map((classeNome) => {
        const turmas = alunosPorStatus[classeNome];

        return (
          <Card key={classeNome} className="overflow-hidden">
            <CardHeader className="border-b bg-muted/30">
              <CardTitle className="text-base">{classeNome}</CardTitle>
            </CardHeader>
            <CardContent className="space-y-4 p-0! py-4">
              {Object.keys(turmas).map((turmaNome, index) => (
                <div
                  key={turmaNome}
                  className={
                    index > 0
                      ? 'mx-4 mt-6 rounded-lg border'
                      : 'mx-4 rounded-lg border'
                  }
                >
                  <div className="rounded-t-lg bg-muted/50 px-4 py-2.5 text-sm font-medium">
                    Turma: {turmaNome}
                  </div>
                  <Table>
                    <TableHeader>
                      <TableRow>
                        <TableHead className="px-4">Nome</TableHead>
                        <TableHead className="px-4">Curso</TableHead>
                        <TableHead className="px-4">Turno</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {turmas[turmaNome].map((aluno) => (
                        <TableRow key={aluno.id}>
                          <TableCell className="px-4">{aluno.nome}</TableCell>
                          <TableCell className="px-4">{aluno.curso}</TableCell>
                          <TableCell className="px-4">{aluno.turno}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </div>
              ))}
            </CardContent>
          </Card>
        );
      })}
    </div>
  );
}
