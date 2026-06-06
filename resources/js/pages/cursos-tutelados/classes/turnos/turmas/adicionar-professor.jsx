"use client"
import { useRouter } from "next/navigation"
import { AdicionarProfessorForm } from "../../../../components/turmas/adicionar-professor-form.jsx"
import { useAdicionarProfessor } from "../../../../hooks/classes/turnos/turmas/useAdicionarProfessor"
import { useProfessores } from "../../../../hooks/professores/useProfessores"
import Loader from "@/components/loader"
import { useDisciplinas } from "@/features/disciplinas/hooks/useDisciplinas"

export function AdicionarProfessorTurma({ instituicaoId, cursoId, turmaId }) {
  const router = useRouter()
  const mutation = useAdicionarProfessor(instituicaoId, cursoId, turmaId)
  const { data: professores, isLoadingProfessores } = useProfessores(instituicaoId, cursoId)
  const { data: disciplinas, isLoadingDisiciplinas } = useDisciplinas()

  if (isLoadingProfessores || isLoadingDisiciplinas) return <Loader />

  return (
    <AdicionarProfessorForm
      title="Adicionar disciplina à turma"
      isPending={mutation.isPending}
      professores={professores?.data ?? []}
      disciplinas={disciplinas ?? []}
      submitFn={(formData) => mutation.mutate(formData, {
        onSuccess: () => router.push(`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}`),
        onError: (error) => alert(error?.response?.data?.message ?? 'Erro ao adicionar professor')
      })}
    />
  )
}
