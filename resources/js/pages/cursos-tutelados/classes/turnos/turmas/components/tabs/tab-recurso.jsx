import LancamentosRecursoTable from '../../disciplinas/notas/components/lancamentos-recurso-table'
import { router, usePage } from '@inertiajs/react'
import { useState } from 'react'

export function TabRecurso({ alunos, instituicaoId, cursoId, turmaId }) {
  const [isPending, setIsPending] = useState(false)

  function handleSubmit(payload) {
    router.post(
      `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/turmas/${turmaId}/notas/recurso`,
      payload,
      {
        preserveScroll: true,
        onStart: () => setIsPending(true),
        onFinish: () => setIsPending(false),
      }
    )
  }

  return (
    <LancamentosRecursoTable
      alunos={alunos ?? []}
      isPending={isPending}
      onSubmit={handleSubmit}
    />
  )
}