"use client"
import { Loader2 } from "lucide-react"
import { useNotasLancamento } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/disciplinas/notas/useNotasLancamento"
import { useCreateLancamentos } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/disciplinas/notas/useCreateLancamento"
import LancamentosTable from "@/features/curso-tutelado/components/classes/turnos/turmas/disciplinas/notas/lancamentos-table"

export function DisciplinaLancamentos({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  disciplinaId
}) {
  const { data, isLoading } = useNotasLancamento(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
    disciplinaId
  )

  const { mutate, isPending } = useCreateLancamentos(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
    disciplinaId
  )

  if (isLoading)
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin size-8" />
      </div>
    )

  if (!data)
    return (
      <div className="flex justify-center py-20">
        <span className="text-muted-foreground text-sm">Sem dados disponíveis.</span>
      </div>
    )

  return (
    <LancamentosTable
      data={data}
      instituicaoId={instituicaoId}
      cursoId={cursoId}
      classeId={classeId}
      turnoId={turnoId}
      turmaId={turmaId}
      disciplinaId={disciplinaId}
      isPending={isPending}
      defaulValues={{
        mac: "",
        npp: "",
        npt: "",
        fi: ""
      }}
      onSubmit={(payload) => mutate(payload)}
    />
  )
}
