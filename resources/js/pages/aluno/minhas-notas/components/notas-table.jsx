import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BookOpenIcon, Filter } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { TrimestroSelector } from './trimestre-selector';
import { useState } from 'react';
import { getStatusVariant } from '@/utils/get-variants';
/**
 * Componente de tabela para exibição de notas do aluno.
 *
 * Estrutura:
 * - Coluna: Disciplina
 * - Colunas: Prova 1, Prova 2, Prova 3, Média Trimestral, Classificação
 *
 * @component
 * @param {Array} data - Array de notas com provas por trimestre
 * @param {number} trimestre - Trimestre selecionado (1, 2 ou 3)
 * @returns {JSX.Element}
 */
export function NotasTable({ data = [] }) {
  const [trimestre, setTrimestre] = useState(1);
  const isEmpty = !data || data.length === 0;
  const formatarNota = (n) =>
    n !== null && n !== undefined ? parseFloat(Number(n).toFixed(1)) : '—';

  return (
    <Card className="w-full gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Notas Detalhadas</CardTitle>

        <CardDescription className="">
          Desempenho nas provas do{' '}
          {trimestre === 1 ? '1º' : trimestre === 2 ? '2º' : '3º'} trimestre
        </CardDescription>

        <CardAction className="flex flex-col gap-2 md:flex-row md:items-center">
          <TrimestroSelector value={trimestre} onChange={setTrimestre} />
        </CardAction>
      </CardHeader>

      <CardContent className="gap-0 p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookOpenIcon}
            title="Sem notas disponíveis"
            description="Aguarde o lançamento de suas notas"
          />
        ) : (
          <div className="w-full overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="min-w-50 px-4">Disciplina</TableHead>

                  <TableHead className="min-w-20 px-4 text-center">
                    Prova 1
                  </TableHead>

                  <TableHead className="min-w-20 px-4 text-center">
                    Prova 2
                  </TableHead>

                  <TableHead className="min-w-20 px-4 text-center">
                    Prova 3
                  </TableHead>

                  <TableHead className="min-w-20 px-4 text-center">
                    Faltas
                  </TableHead>

                  <TableHead className="min-w-25 px-4 text-center">
                    Média Trim.
                  </TableHead>

                  <TableHead className="min-w-30 px-4 text-center">
                    Classificação
                  </TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {data.map((nota) => {
                  const trimData = nota.trimestres[trimestre];
                  const [prova1, prova2, prova3] = trimData.provas;

                  return (
                    <TableRow key={nota.id}>
                      <TableCell className="px-4 font-medium">
                        {nota.disciplina}
                      </TableCell>

                      <TableCell className="px-4 text-center">
                        {formatarNota(prova1)}
                      </TableCell>

                      <TableCell className="px-4 text-center">
                        {formatarNota(prova2)}
                      </TableCell>

                      <TableCell className="px-4 text-center">
                        {formatarNota(prova3)}
                      </TableCell>

                      <TableCell className="px-4 text-center">
                        {trimData.faltas !== null &&
                        trimData.faltas !== undefined
                          ? trimData.faltas
                          : '-'}
                      </TableCell>

                      <TableCell className="px-4 text-center font-semibold">
                        {formatarNota(trimData.media)}
                      </TableCell>

                      <TableCell className="px-4 text-center">
                        {trimData.situacao ? (
                          <Badge variant={getStatusVariant(trimData.situacao)}>
                            {trimData.situacao}
                          </Badge>
                        ) : (
                          <span className="text-xs text-muted-foreground">
                            —
                          </span>
                        )}
                      </TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
