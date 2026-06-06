"use client"
import { useRouter } from "next/navigation"
import { BancaForm } from "../components/banca-form"
import { useAdicionarJurado } from "../hooks/useAdicionarJurado"
import { useProfessores as useProfessoresGeral } from "@/features/professores/hooks/useProfessores"
import Loader from "@/components/loader"

export function BancaCreate({ grupoId }) {
  const router = useRouter()
  const mutation = useAdicionarJurado(grupoId)
  const { data: professores, isLoading } = useProfessoresGeral()

  if (isLoading) return <Loader />

  return (
    <BancaForm
      title="Adicionar jurado à banca"
      isPending={mutation.isPending}
      professores={professores ?? []}
      submitFn={(formData) => mutation.mutate(formData, {
        onSuccess: () => router.push(`/dashboard/pap/grupos/${grupoId}`),
        onError: (error) => alert(error?.response?.data?.message ?? 'Erro ao adicionar jurado')
      })}
    />
  )
}