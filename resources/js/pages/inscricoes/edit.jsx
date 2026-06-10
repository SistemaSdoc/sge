
import { InscricaoForm } from "../components/inscricao-form"
import { useRouter } from "next/navigation"
import Loader from "@/components/loader"

export function InscricaoEdit({ id }) {
  const { data, isLoading } = useInscricao(id)
  const mutation = useUpdateInscricao(id)
  const router = useRouter()

  if (isLoading) return <Loader />

  return (
    <InscricaoForm
      title="Editar featureName"
      defaultValues={{
        campo1: data?.campo1,
      }}
      submitFn={(data) => mutation.mutate(data ?? {}, {
        onSuccess: () => router.push('/inscricoes'),
        onError: () => alert('Erro ao actualizar')
      })}
    />
  )
}