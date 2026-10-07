import { SummaryItem } from './summary-item';

export function StateHappening({ aula }) {
  const disciplina = aula.disciplina.sigla ?? aula.disciplina.nome;

  return (
    <SummaryItem className="border-emerald-200 bg-emerald-50 dark:border-emerald-900/30 dark:bg-emerald-950/20">
      <span className="text-emerald-700 dark:text-emerald-300">
        <span className="font-semibold">A decorrer:</span> {disciplina}
        {aula.professor?.nome && ` com o Professor ${aula.professor.nome}`}
        {aula.turma?.nome && ` na turma ${aula.turma.nome}`} até{' '}
        {aula.horario.hora_fim}
      </span>
    </SummaryItem>
  );
}