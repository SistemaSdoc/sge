import { Fragment } from 'react';
import { TableBody, TableCell, TableRow } from '@/components/ui/table';
import { ResultadoBadge } from '../../resultado-badge';
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { corFaltas, corNota } from '../../utils';

export function FinalPautaBody({ alunos, disciplinas }) {
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
            const nota = aluno.notas[disciplina.id];

            return (
              <Fragment key={`${disciplina.id}-cells`}>
                <Fragment key={`${disciplina.id}-cells`}>
                  <TableCell className="border-r bg-card/50 px-4 text-center">
                    <span className={corNota(nota?.t1)}>{nota?.t1 ?? '—'}</span>
                  </TableCell>

                  <TableCell className="border-r px-4 text-center">
                    <span className={corNota(nota?.t2)}>{nota?.t2 ?? '—'}</span>
                  </TableCell>

                  <TableCell className="border-r px-4 text-center">
                    <span className={corNota(nota?.t3)}>{nota?.t3 ?? '—'}</span>
                  </TableCell>

                  <TableCell className="border-r px-4 text-center">
                    <span className={corFaltas(nota?.total_faltas)}>
                      {nota?.total_faltas ?? '—'}
                    </span>
                  </TableCell>

                  <TableCell className="border-r px-4 text-center">
                    <span className={corNota(nota?.mf)}>{nota?.mf ?? '—'}</span>
                  </TableCell>
                </Fragment>
              </Fragment>
            );
          })}

          {/* Célula - Resultado */}
          <TableCell className="sticky right-0 z-10 bg-background px-4 shadow-[-1px_0_0_0_hsl(var(--border))] transition-colors group-hover:bg-muted">
            <span className="absolute top-0 left-0 h-full w-px bg-border" />
            <div className="flex flex-col items-end gap-1">
              {aluno.resultado === 'recurso' &&
              aluno.disciplinas_recurso?.length > 0 ? (
                <TooltipProvider>
                  <Tooltip>
                    <TooltipTrigger asChild>
                      <span>
                        <ResultadoBadge resultado={aluno.resultado} />
                      </span>
                    </TooltipTrigger>
                    <TooltipContent side="left">
                      <p className="mb-1 text-xs font-medium">
                        Disciplinas em recurso:
                      </p>
                      <p className="text-xs">
                        {disciplinas
                          .filter((d) =>
                            aluno.disciplinas_recurso.includes(d.id),
                          )
                          .map((d) => d.sigla)
                          .join(', ')}
                      </p>
                    </TooltipContent>
                  </Tooltip>
                </TooltipProvider>
              ) : (
                <ResultadoBadge resultado={aluno.resultado} />
              )}

              {aluno.resultado === 'transita_com_deficiencia' &&
                aluno.deficiencias?.length > 0 && (
                  <span className="text-xs text-muted-foreground">
                    Def:{' '}
                    {disciplinas
                      .filter((d) => aluno.deficiencias.includes(d.id))
                      .map((d) => d.sigla)
                      .join(', ')}
                  </span>
                )}
            </div>
          </TableCell>
        </TableRow>
      ))}
    </TableBody>
  );
}
