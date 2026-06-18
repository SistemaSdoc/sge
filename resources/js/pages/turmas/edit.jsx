"use client"

import { useTurma } from "../hooks/useTurma"
import { useUpdateTurma } from "../hooks/useUpdateTurma"
import { TurmaForm } from "../components/turma-form"
import { useRouter } from "next/navigation"
import Loader from "@/components/loader"
import { useClasses } from "@/features/classes/hooks/useClasses"

export function TurmaEdit({ id }) {
  const { data, isLoading } = useTurma(id)
  const mutation = useUpdateTurma(id)
  const router = useRouter()
  const { data: classes } = useClasses()

  if (isLoading) return <Loader />

  return (
    <TurmaForm
      title="Editar Turma"
      classes={classes ?? []}
      defaultValues={{
        nome: data?.nome,
        max_alunos: data?.max_alunos,
        classe_id: data?.classe_id,
      }}
      submitFn={(data) => mutation.mutate(data ?? {}, {
        onSuccess: () => router.push('/dashboard/turmas'),
        onError: () => alert('Erro ao actualizar')
      })}
    />
  )
}