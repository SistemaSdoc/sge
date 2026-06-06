"use client"
import { useState, useEffect } from "react"
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from "@/components/ui/pagination"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Loader2, ClipboardListIcon } from "lucide-react"
import { EmptyState } from "@/components/empty-state"
import { mediaTrimestral } from "@/utils/media-trimestral"
import { verificarSituacao } from "@/utils/verificar-situacao"

function buildInitialNotas(alunos, periodo) {
  const state = {}
  for (const aluno of alunos) {
    const nota = aluno.notas?.[periodo] ?? {}
    state[aluno.turma_aluno_id] = {
      mac: nota.mac ?? "",
      npp: nota.nota_prova_professor ?? "",
      npt: nota.nota_prova_trimestral ?? "",
      faltas: nota.faltas ?? "",
    }
  }
  return state
}

export default function LancamentosTable({
  data,
  onSubmit,
  isPending,
  defaulValues,
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  disciplinaId
}) {
  const [periodo, setPeriodo] = useState("1")
  const [notas, setNotas] = useState({})
  const isEmpty = !data?.alunos || data?.alunos?.length === 0;


  useEffect(() => {
    if (data?.alunos) {
      setNotas(buildInitialNotas(data.alunos, periodo))
    }
  }, [data, periodo])

  function handleChange(turmaAlunoId, campo, valor) {
    setNotas(prev => ({
      ...prev,
      [turmaAlunoId]: {
        ...prev[turmaAlunoId],
        [campo]: valor,
      }
    }))
  }

  function handleSubmit() {
    const payload = {
      tdp_id: data.tdp_id,
      periodo: parseInt(periodo),
      notas: {},
    }
    for (const [turmaAlunoId, valores] of Object.entries(notas)) {
      payload.notas[turmaAlunoId] = {
        mac: valores.mac !== "" ? valores.mac : null,
        npp: valores.npp !== "" ? valores.npp : null,
        npt: valores.npt !== "" ? valores.npt : null,
        faltas: valores.faltas !== "" ? valores.faltas : 0,
      }
    }
    onSubmit(payload)
  }

  const alunos = data?.alunos ?? []

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <div>
          <CardTitle>{data?.disciplina?.nome}</CardTitle>
          <CardDescription>Preencha as notas dos alunos para o trimestre seleccionado</CardDescription>
        </div>
        <CardAction className="flex items-center gap-3">
          <Select value={periodo} onValueChange={setPeriodo}>
            <SelectTrigger className="w-40">
              <SelectValue placeholder="Trimestre" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="1">1º Trimestre</SelectItem>
              <SelectItem value="2">2º Trimestre</SelectItem>
              <SelectItem value="3">3º Trimestre</SelectItem>
            </SelectContent>
          </Select>
          <Button onClick={handleSubmit} disabled={isPending}>
            {isPending ? <Loader2 className="animate-spin size-4 mr-2" /> : null}
            Lançar
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={ClipboardListIcon}
            title="Nenhum lançamento"
            description="Nenhuma nota para registar"
            action={{
              label: "Lançar Notas",
              href: `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${disciplinaId}/notas/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-1! px-4">#</TableHead>
                <TableHead className="w-48 px-4">Aluno</TableHead>
                <TableHead className="w-1 text-center">MAC</TableHead>
                <TableHead className="w-1 text-center">NPP</TableHead>
                <TableHead className="w-1 text-center">NPT</TableHead>
                <TableHead className="w-1 text-center">MT</TableHead>
                <TableHead className="w-1 text-center">F.I</TableHead>
                <TableHead className="w-20 text-end px-4">Resultado</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {alunos.map((aluno, index) => {
                const n = notas[aluno.turma_aluno_id] ?? {}
                const mt = mediaTrimestral(n.mac, n.npp, n.npt)
                console.log(aluno.nome, { mac: n.mac, npp: n.npp, npt: n.npt, mt })
                const mediaFinal = aluno.notas?.[periodo]?.media_final ?? null
                const situacao = verificarSituacao(mt, Number(n.faltas))

                return (
                  <TableRow key={aluno.turma_aluno_id}>
                    <TableCell className="px-4">{index + 1}</TableCell>
                    <TableCell className="px-4">{aluno.nome}</TableCell>

                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        value={n.mac ?? ""}
                        onChange={e => handleChange(aluno.turma_aluno_id, "mac", e.target.value)}
                        className="text-center"
                      />
                    </TableCell>

                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        value={n.npp ?? ""}
                        onChange={e => handleChange(aluno.turma_aluno_id, "npp", e.target.value)}
                        className="text-center"
                      />
                    </TableCell>

                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        max={20}
                        value={n.npt ?? ""}
                        onChange={e => handleChange(aluno.turma_aluno_id, "npt", e.target.value)}
                        className="text-center"
                      />
                    </TableCell>

                    <TableCell className="text-center font-medium">
                      {mt ?? "-"}
                    </TableCell>

                    <TableCell>
                      <Input
                        type="number"
                        min={0}
                        value={n.faltas ?? ""}
                        onChange={e => handleChange(aluno.turma_aluno_id, "faltas", e.target.value)}
                        className="text-center"
                      />
                    </TableCell>

                    <TableCell className="text-end px-4">
                      {situacao === "APTO" && (
                        <Badge className="bg-green-50 text-green-500">APTO</Badge>
                      )}
                      {situacao === "N/APTO" && (
                        <Badge className="bg-red-50 text-red-500">NÃO APTO</Badge>
                      )}
                      {situacao === null && (
                        <span className="text-muted-foreground text-sm">-</span>
                      )}
                    </TableCell>
                  </TableRow>
                )
              })}
            </TableBody>
          </Table>
        )}
        {!isEmpty && (<CardFooter className="justify-between">
          <span className="text-muted-foreground">
            {alunos.length} aluno{alunos.length !== 1 ? "s" : ""}
          </span>
        </CardFooter>
        )}
      </CardContent>
    </Card>
  )
}
