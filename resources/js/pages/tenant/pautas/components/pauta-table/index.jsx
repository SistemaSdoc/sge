import { Table } from '@/components/ui/table';
import {
  Card,
  CardContent,
  CardHeader,
  CardTitle,
  CardDescription,
} from '@/components/ui/card';
import { FileTextIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { TrimestralPautaHeader } from './types/trimestral/header';
import { TrimestralPautaBody } from './types/trimestral/body';
import { FinalPautaHeader } from './types/final/header';
import { FinalPautaBody } from './types/final/body';
import { RecursoPautaHeader } from './types/recurso/header';
import { RecursoPautaBody } from './types/recurso/body';
import TablePagination from '@/components/table-pagination';
import { PautaCardActions } from './card-actions';
import { PERIODOS } from './utils';

export function PautaTable({
  data,
  periodo,
  setPeriodo,
  disciplinas = [],
  alunos = [],
  pagination = null,
  onPageChange,
  params,
}) {
  const isEmpty = alunos.length === 0;
  const periodoLabel =
    PERIODOS.find((item) => item.value === periodo)?.label ?? periodo;
  const isTrimestral = ['1', '2', '3'].includes(String(periodo));

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Turma {data?.turma?.nome}</CardTitle>
        <CardDescription>{periodoLabel}</CardDescription>
        <PautaCardActions
          periodo={periodo}
          setPeriodo={setPeriodo}
          params={params}
        />
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={FileTextIcon}
            title="Nenhuma pauta disponível"
            description="Ainda não existem notas lançadas para este período"
          />
        ) : (
          <div className="relative w-full overflow-x-auto">
            <Table>
              {periodo === 'final' && (
                <>
                  <FinalPautaHeader disciplinas={disciplinas} />
                  <FinalPautaBody alunos={alunos} disciplinas={disciplinas} />
                </>
              )}

              {periodo === 'recurso' && (
                <>
                  <RecursoPautaHeader disciplinas={disciplinas} />
                  <RecursoPautaBody alunos={alunos} disciplinas={disciplinas} />
                </>
              )}

              {isTrimestral && (
                <>
                  <TrimestralPautaHeader
                    disciplinas={disciplinas}
                    periodo={periodo}
                  />
                  <TrimestralPautaBody
                    alunos={alunos}
                    disciplinas={disciplinas}
                  />
                </>
              )}
            </Table>
          </div>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
