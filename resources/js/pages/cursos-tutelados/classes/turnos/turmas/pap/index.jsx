"use client"
import { useGruposPap } from "../hooks/useGruposPap"
import { useDeleteGrupoPap } from "../hooks/useDeleteGrupoPap"
import { GrupoPapTable } from "../components/grupo-pap-table"
import Loader from "@/components/loader"
import { GrupoPapCards } from "../components/grupo-pap-cards"

export function GruposPapIndex() {
  const { data, isLoading } = useGruposPap()
  const { mutate: deleteGrupo } = useDeleteGrupoPap()

  if (isLoading) return <Loader />

  return (
    <GrupoPapCards
      grupos={data ?? []}
      deleteFn={(id) => deleteGrupo(id)}
    />
  )
}