"use client"
import { Loader2 } from "lucide-react"
import { useNotasLancamento } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/disciplinas/notas/useNotasLancamento"
import { useCreateLancamentos } from "@/features/curso-tutelado/hooks/classes/turnos/turmas/disciplinas/notas/useCreateLancamento"
import NotasTable from "../../../../../../components/classes/turnos/turmas/disciplinas/notas/notas-table"

export function DisciplinaNotas({
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

  if (isLoading)
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin size-8" />
      </div>
    )

  return (
    <NotasTable
      data={data}
      instituicaoId={instituicaoId}
      cursoId={cursoId}
      classeId={classeId}
      turnoId={turnoId}
      turmaId={turmaId}
      disciplinaId={disciplinaId}
    />
  )
}
