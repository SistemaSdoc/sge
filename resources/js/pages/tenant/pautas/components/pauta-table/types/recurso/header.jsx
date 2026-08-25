import { Fragment } from 'react';
import { TableHeader, TableRow, TableHead } from '@/components/ui/table';

export function RecursoPautaHeader({ disciplinas }) {
  return (
    <TableHeader>
      {/*Linha 1 - #, Nome, Disciplinas, Resultado */}
      <TableRow className="group bg-muted hover:bg-muted">
        {/* Header - # */}
        <TableHead className="sticky left-0 z-30 w-10 bg-muted px-4">
          #
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - Nome */}
        <TableHead className="sticky left-10 z-20 bg-muted px-4">
          Nome
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - Nome das disciplinas */}
        {disciplinas.map((disciplina) => (
          <TableHead
            key={disciplina.id}
            className="min-w-[16rem] border-r px-4 text-center"
          >
            {disciplina.sigla}
          </TableHead>
        ))}

        {/* Header - Resultado */}
        <TableHead className="sticky right-0 z-20 bg-muted px-4 text-end">
          <span className="absolute top-0 left-0 h-full w-[0.5px] border-l" />
          Resultado
        </TableHead>
      </TableRow>
    </TableHeader>
  );
}
