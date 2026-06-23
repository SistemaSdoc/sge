import LancamentosRecursoTable from '../../disciplinas/notas/components/lancamentos-recurso-table'
import { router, usePage } from '@inertiajs/react'
import { useState } from 'react'
import { storeRecurso } from '@/actions/App/Http/Controllers/NotaController'

export function TabRecurso({ alunos, instituicaoId, cursoId, turmaId }) {
  const [isPending, setIsPending] = useState(false)

  function handleSubmit(payload) {
    router.post(
      storeRecurso({
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
      alunos={alunos ?? []}
      isPending={isPending}
      onSubmit={handleSubmit}
    />
  )
}