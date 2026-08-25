import { Fragment } from 'react';
import { TableBody, TableCell, TableRow } from '@/components/ui/table';
import { ResultadoBadge } from '../../resultado-badge';
import { corNota } from '../../utils';

export function RecursoPautaBody({ alunos, disciplinas }) {
  return (
    <TableBody>
      {alunos.map((aluno) => (
        /* Linha da tabela */
        <TableRow key={aluno.aluno_id} className="group">
          {/* Célula - # */}
          <TableCell className="sticky left-0 z-30 w-10 bg-background px-4 text-muted-foreground transition-colors group-hover:bg-muted">
            {aluno.numero}
            <span className="absolute top-0 right-0 h-full w-px bg-border" />
          </TableCell>

          {/* Célula - nome */}
          <TableCell className="sticky left-10 z-20 bg-background px-4 font-medium transition-colors group-hover:bg-muted">
            {aluno.nome}
            <span className="absolute top-0 right-0 h-full w-px bg-border" />
          </TableCell>

          {/* Célula - Notas das disciplinas */}
          {disciplinas.map((disciplina) => {
            const nota = aluno.notas[disciplina.id] ?? {}; // ← acesso por UUID

            return (
              <Fragment key={disciplina.id}>
                <TableCell className="border-r px-4 text-center">
                  <span className={corNota(nota?.mf)}>{nota?.mf ?? '—'}</span>
                </TableCell>
              </Fragment>
            );
          })}

          {/* Célula - Resultado */}
          <TableCell className="sticky right-0 z-10 bg-background px-4 shadow-[-1px_0_0_0_hsl(var(--border))] transition-colors group-hover:bg-muted">
            <span className="absolute top-0 left-0 h-full w-px bg-border" />
            <ResultadoBadge resultado={aluno.resultado} />
          </TableCell>
        </TableRow>
      ))}
    </TableBody>
  );
}
