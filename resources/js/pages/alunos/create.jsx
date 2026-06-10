"use client"

import { useRouter } from "next/navigation"
import { AlunoForm } from "../components/aluno-form"
import { useCreateAluno } from "../hooks/useCreateAluno"

export function AlunoCreate() {
  const mutation = useCreateAluno()
  const router = useRouter()

  return (
    <div>
      <AlunoForm
        title="Adicionar aluno"
        defaultValues={{
          campo1: "",  
        }}
        submitFn={(data) => mutation.mutate(data, {
          onSuccess: () => router.push('/dashboard/alunos'),
          onError: () => alert('Erro ao cadastrar')
        })}
      />
    </div>
  )
}