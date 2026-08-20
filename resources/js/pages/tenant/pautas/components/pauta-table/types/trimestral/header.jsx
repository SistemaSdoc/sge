import { TableHeader, TableRow, TableHead } from '@/components/ui/table';
import { Fragment } from 'react';

export function TrimestralPautaHeader({ disciplinas, periodo }) {
  return (
    <TableHeader>
      {/*Linha 1 - Nomes das disciplinas */}
      <TableRow className="group bg-muted hover:bg-muted">
        {/* Header - Célula em branco */}
        <TableHead className="sticky left-0 z-40 w-10 bg-muted px-4">
          &nbsp;
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - Célula em branco */}
        <TableHead className="sticky left-10 z-30 bg-muted px-4">
          &nbsp;
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - Nome das disciplinas */}
        {disciplinas.map((disciplina) => (
          <TableHead
            key={disciplina.id}
            className="border-r px-4 text-center"
            colSpan={2}
          >
            {disciplina.sigla}
          </TableHead>
        ))}

        {/* Header - Célula em branco */}
        <TableHead className="sticky right-0 z-20 bg-muted px-4 text-end">
          <span className="absolute top-0 left-0 h-full w-[0.5px] border-l" />
          &nbsp;
        </TableHead>
      </TableRow>

      {/*Linha 2 - #, Nome, MT/F, Resultado */}
      <TableRow className="group">
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

        {/* Header - MT/F por disciplina */}
        {disciplinas.map((disciplina) => (
          <Fragment key={`${disciplina.id}-sub`}>
            <TableHead className="border-r bg-muted px-4 text-center">
              MT{periodo}
            </TableHead>

            <TableHead className="border-r bg-muted px-4 text-center">
              F.I
            </TableHead>
          </Fragment>
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
