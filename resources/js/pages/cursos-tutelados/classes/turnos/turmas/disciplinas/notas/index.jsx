import { usePage } from '@inertiajs/react'
import NotasTable from '../../../../../components/classes/turnos/turmas/disciplinas/notas/notas-table'

export default function Index() {
  const {
    instituicaoId, cursoId, classeId, turnoId, turmaId,
    tdpId, disciplina, alunos,
  } = usePage().props

  return (
    <NotasTable
      alunos={alunos}
      disciplina={disciplina}
      tdpId={tdpId}
      instituicaoId={instituicaoId}
      cursoId={cursoId}
      classeId={classeId}
      turnoId={turnoId}
      turmaId={turmaId}
    />
  )
}