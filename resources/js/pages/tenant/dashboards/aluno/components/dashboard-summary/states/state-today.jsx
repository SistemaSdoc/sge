import { SummaryItem } from './summary-item';

export function StateToday({ aula, timeLeft }) {
  const disciplina = aula.disciplina.sigla ?? aula.disciplina.nome;

  return (
    <SummaryItem className="border-yellow-200 bg-yellow-50 dark:border-yellow-900/30 dark:bg-yellow-950/20">
      <span className="text-yellow-700 dark:text-yellow-300">
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