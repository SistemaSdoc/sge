"use client"
import { TurmaForm } from "@/features/curso-tutelado/components/classes/turnos/turmas/turma-form"
import { useCreateTurma } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/useCreateTurma"
import { useDisciplinas } from "@/features/curso-tutelado/hooks/classes/turnos/disciplinas/useDisciplinas"
import { useRouter } from "next/navigation"

export function CreateTurma({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
}) {
  const router = useRouter()
  const mutation = useCreateTurma(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
  )

  return (
    <TurmaForm
      title="Criar turma"
      isPending={mutation.isPending}
      defaultValues={{
        nome: "",
        max_alunos: ""
      }}
      submitFn={(formData) => mutation.mutate(formData, {
        onSuccess: () => router.push(`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}`),
        onError: () => alert("Erro ao associar disciplina")
      })}
    />
  )
}