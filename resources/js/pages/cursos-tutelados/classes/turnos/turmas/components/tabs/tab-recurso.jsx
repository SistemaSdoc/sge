import LancamentosRecursoTable from '../../disciplinas/notas/components/lancamentos-recurso-table'
import { router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import { store } from '@/actions/App/Http/Controllers/NotaDisciplinaRecursoController'

export function TabRecurso({ alunos, params }) {
  const [isPending, setIsPending] = useState(false)

  function handleSubmit(payload) {
    router.post(
      store({
        instituicao: instituicaoId,
        cursoTutelado: cursoId,
        turma: turmaId,
      }).url,
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
      alunos={alunos.data ?? []}
      isPending={isPending}
      onSubmit={handleSubmit}
    />
  )
}