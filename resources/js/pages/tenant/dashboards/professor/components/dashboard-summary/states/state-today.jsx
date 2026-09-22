import { SummaryItem } from './summary-item';
  import { getBadgeVariantForDay, getDayCategory } from '@/utils/get-badge-color';


export function StateToday({ aula, timeLeft }) {
  const disciplina = aula.disciplina.sigla ?? aula.disciplina.nome;
  const category = getDayCategory(aula.dia);
  const colorClass = getBadgeVariantForDay(category);

  return (
    <SummaryItem className={colorClass}>
      <span>
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