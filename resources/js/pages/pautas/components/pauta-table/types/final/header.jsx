import { Fragment } from 'react';
import { TableHeader, TableRow, TableHead } from '@/components/ui/table';

export function FinalPautaHeader({ disciplinas }) {
  return (
    <TableHeader>
      {/* Linha 1 — nomes das disciplinas */}
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
            className="min-w-[16rem] border-r bg-muted px-4 text-center"
            colSpan={5}
          >
            {disciplina.sigla}
          </TableHead>
        ))}

        {/* Header - Resultado (célula em branco na linha 1) */}
        <TableHead className="sticky right-0 z-20 bg-muted px-4 text-end">
          <span className="absolute top-0 left-0 h-full w-[0.5px] border-l" />
          &nbsp;
        </TableHead>
      </TableRow>

      {/* Linha 2 — #, Nome, 1T/2T/3T/MF, Faltas, Resultado */}
      <TableRow className="group">
        {/* Header - # */}
        <TableHead className="sticky left-0 z-30 w-10 bg-muted px-4 text-secondary-foreground">
          #
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - Nome */}
        <TableHead className="sticky left-10 z-20 bg-muted px-4">
          Nome
          <span className="absolute top-0 right-0 h-full w-px bg-border" />
        </TableHead>

        {/* Header - 1T/2T/3T/MF por disciplina */}
        {disciplinas.map((disciplina) => (
          <Fragment key={`${disciplina.id}-sub`}>
            <TableHead className="border-r border-l bg-muted px-4 text-center">
              1T
            </TableHead>
            <TableHead className="border-r bg-muted px-4 text-center">
              2T
            </TableHead>
            <TableHead className="border-r bg-muted px-4 text-center">
              3T
            </TableHead>
            <TableHead className="border-r bg-muted px-4 text-center">
              F
            </TableHead>
            <TableHead className="border-r bg-muted px-4 text-center">
              MF
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
