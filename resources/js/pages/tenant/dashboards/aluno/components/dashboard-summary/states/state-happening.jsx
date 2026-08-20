import { SummaryItem } from './summary-item';

export function StateHappening({ aula }) {
  const disciplina = aula.disciplina.sigla ?? aula.disciplina.nome;

  return (
    <SummaryItem className="border-blue-200 bg-blue-50 dark:border-blue-900/30 dark:bg-blue-950/20">
      <span className="text-blue-700 dark:text-blue-300">
        <span className="font-semibold">A decorrer:</span> {disciplina}
        {aula.professor?.nome && ` com ${aula.professor.nome}`}
        {aula.turma?.nome && ` na turma ${aula.turma.nome}`} até{' '}
        {aula.horario.hora_fim}
      </span>
    </SummaryItem>
  );
}
