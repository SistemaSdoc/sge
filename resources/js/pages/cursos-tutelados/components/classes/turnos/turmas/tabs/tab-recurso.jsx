"use client"
import { useQuery } from "@tanstack/react-query"
import { getPauta } from "@/features/curso-tutelado/api/classes/turnos/turmas/getPauta"
import { useCreateLancamentoRecurso } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/useCreateLancamentoRecurso"
import LancamentosRecursoTable from "@/features/curso-tutelado/components/classes/turnos/turmas/disciplinas/notas/lancamentos-recurso-table"
import { Loader2 } from "lucide-react"

export function TabRecurso({ turmaId }) {
  const { data, isLoading } = useQuery({
    queryKey: ["pauta", turmaId, "recurso"],
    queryFn: () => getPauta({ turmaId, periodo: "recurso" }),
    enabled: !!turmaId,
  })

  const { mutate, isPending } = useCreateLancamentoRecurso(turmaId)

  if (isLoading) {
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin size-6 text-muted-foreground" />
      </div>
    )
  }

  return (
    <LancamentosRecursoTable
      data={data}
      onSubmit={mutate}
      isPending={isPending}
    />
  )
}