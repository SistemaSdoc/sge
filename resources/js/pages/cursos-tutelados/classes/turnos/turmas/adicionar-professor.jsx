"use client"
import { useRouter } from "next/navigation"
import { AdicionarProfessorForm } from "../../../../components/turmas/adicionar-professor-form.jsx"

import Loader from "@/components/loader"
import { useDisciplinas } from "@/features/disciplinas/hooks/useDisciplinas"

export function AdicionarProfessorTurma({ instituicaoId, cursoId, turmaId }) {


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
