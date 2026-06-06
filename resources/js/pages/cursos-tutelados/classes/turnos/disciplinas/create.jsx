"use client"
import DisciplinaForm from "@/features/curso-tutelado/components/classes/turnos/disciplinas/disciplina-form"
import { useCreateDisciplina } from "@/features/curso-tutelado/hooks/classes/turnos/disciplinas/useCreateDisciplina"
import { useDisciplinas } from "@/features/disciplinas/hooks/useDisciplinas"
import { useRouter } from "next/navigation"

export function CreateDisciplinas({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
}) {
  const router = useRouter()
  const { data, isLoading } = useDisciplinas()
  const mutation = useCreateDisciplina(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
  )

  return (
    <DisciplinaForm
      title="Associar disciplina"
      isLoading={isLoading}
      isPending={mutation.isPending}
      disciplinas={data ?? []}
      defaultValues={{
        disciplina_ids: []
      }}
      submitFn={(formData) => mutation.mutate(formData, {
        onSuccess: () => router.push(`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}`),
        onError: () => alert("Erro ao associar disciplina")
      })}
    />
  )
}