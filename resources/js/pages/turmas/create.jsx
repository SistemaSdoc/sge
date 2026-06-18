"use client"

import { useRouter } from "next/navigation"
import { TurmaForm } from "../components/turma-form"
import { useCreateTurma } from "../hooks/useCreateTurma"
import { useClasses } from "@/features/classes/hooks/useClasses"

export function TurmaCreate() {
  const mutation = useCreateTurma()
  const { data } = useClasses()
  const router = useRouter()

  return (
    <div>
      <TurmaForm
        title="Adicionar featureName"
        classes={data ?? []}
        defaultValues={{
          nome: "",
          max_alunos: "",
          classe_id: [],
        }}
        submitFn={(data) => mutation.mutate(data, {
          onSuccess: () => router.push('/dashboard/turmas'),
          onError: () => alert('Erro ao cadastrar')
        })}
      />
    </div>
  )
}