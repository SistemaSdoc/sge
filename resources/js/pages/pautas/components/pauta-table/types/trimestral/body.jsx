import { TableBody, TableCell, TableRow } from '@/components/ui/table';
import { ResultadoBadge } from '../../resultado-badge';
import { Fragment } from 'react';
import { corFaltas, corNota } from '../../utils';

export function TrimestralPautaBody({ alunos, disciplinas }) {
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
            <span className="absolute top-0 right-0 h-full w-[0.5px] bg-border" />
          </TableCell>
          {/* Célula - Notas das disciplinas */}
          {disciplinas.map((disciplina) => {
            const nota = aluno.notas[disciplina.id]; // ← acesso por UUID
            return (
              <Fragment key={disciplina.id}>
                <TableCell className="border-r px-4 text-center">
                  <span className={corNota(nota?.media)}>{nota?.media ?? '—'}</span>
                </TableCell>

                <TableCell className="border-r px-4 text-center">
                   <span className={corFaltas(nota?.faltas)}>{nota?.faltas ?? '—'}</span>
                  
                </TableCell>
              </Fragment>
            );
          })}

          {/* Célula - Resultado */}
          <TableCell className="sticky right-0 z-10 bg-background px-4 transition-colors group-hover:bg-muted">
            <span className="absolute top-0 left-0 h-full w-[0.5px] border-l" />
            <div className="flex justify-end">
              {(() => {
                const notasValues = Object.values(aluno.notas);
                const todasLancadas = notasValues.every(
                  (n) => n?.situacao !== 'sem_notas',
                );

                if (!todasLancadas) {
                  return <ResultadoBadge resultado="incompleto" />;
                }

                const temNaoApto = notasValues.some(
                  (n) => n?.situacao === 'N/APTO',
                );
                return (
                  <ResultadoBadge resultado={temNaoApto ? 'N/APTO' : 'APTO'} />
                );
              })()}
            </div>
          </TableCell>
        </TableRow>
      ))}
    </TableBody>
  );
}
