import { router, usePage } from '@inertiajs/react'
import LancamentosTable from '../../../../../components/classes/turnos/turmas/disciplinas/notas/lancamentos-table'

export default function Create() {
  const {
    instituicaoId, cursoId, classeId, turnoId, turmaId,
    tdpId, disciplina, alunos,
  } = usePage().props

  const [isPending, setIsPending] = useState(false)

  function handleSubmit(payload) {
    router.post(
      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${disciplina.id}/notas`,
      payload,
      {
        preserveScroll: true,
        onStart: () => setIsPending(true),
        onFinish: () => setIsPending(false),
      }
    )
  }

  return (
    <LancamentosTable
      tdpId={tdpId}
      disciplina={disciplina}
      alunos={alunos ?? []}
      isPending={isPending}
      onSubmit={handleSubmit}
    />
  )
}