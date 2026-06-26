import { SummaryItem } from './summary-item';

export function StateToday({ aula, timeLeft }) {
  const disciplina = aula.disciplina.sigla ?? aula.disciplina.nome;

  return (
    <SummaryItem className="border-amber-200 bg-amber-50 dark:border-amber-900/30 dark:bg-amber-950/20">
      <span className="text-amber-700 dark:text-amber-300">
        <span className="font-semibold">
          Aula de {disciplina}
          {aula.turma?.nome && ` na turma ${aula.turma.nome}`}
        </span>{' '}
        em <span className="font-semibold">{timeLeft}</span> •{' '}
        {aula.horario.hora_inicio}–{aula.horario.hora_fim}
      </span>
    </SummaryItem>
  );
}
