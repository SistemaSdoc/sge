"use client"
import { ProfessorForm } from "@/features/curso-tutelado/components/classes/turnos/turmas/disciplinas/professores/professor-form"
import { useCreateTurma } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/useCreateTurma"
import { useDisciplinas } from "@/features/curso-tutelado/hooks/classes/turnos/disciplinas/useDisciplinas"
import { useRouter } from "next/navigation"
import { useDefinirProfessor } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/disciplinas/professores/useDefinirProfessor"
import { useProfessores } from "@/features/curso-tutelado/hooks/professores/useProfessores"

export function CreateProfessor({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  disciplinaId
}) {
  const router = useRouter()
  const mutation = useDefinirProfessor(instituicaoId, cursoId, classeId, turnoId, turmaId, disciplinaId)
  const { data: professores, isLoadingProfessores } = useProfessores(instituicaoId, cursoId)
  const { data: disciplinas, isLoadingDisiciplinas } = useDisciplinas(instituicaoId, cursoId, classeId, turnoId, turmaId)

  if (isLoadingProfessores || isLoadingDisiciplinas) return <Loader />

  return (
    <ProfessorForm
      title="Definir professor"
      isPending={mutation.isPending}
      professores={professores ?? []}
      disciplinas={disciplinas ?? []}
      submitFn={(formData) => mutation.mutate(formData, {
        onSuccess: () => router.push(`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}`),
        onError: (error) => alert(error?.response?.data?.message ?? 'Erro ao adicionar professor')
      })}
    />
  )
}