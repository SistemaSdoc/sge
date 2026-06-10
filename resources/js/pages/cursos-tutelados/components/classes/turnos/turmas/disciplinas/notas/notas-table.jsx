import { useState, useEffect } from "react"
import { Link } from "@inertiajs/react"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Card, CardAction, CardContent, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Button } from "@/components/ui/button"
import { Badge } from "@/components/ui/badge"
import { FileTextIcon } from "lucide-react"
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

export default function NotasTable({
  alunos = [],
  disciplina,
  tdpId,
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
}) {
  const [periodo, setPeriodo] = useState("1")
  const [notas, setNotas] = useState({})

  const isEmpty = alunos.length === 0

  useEffect(() => {
    setNotas(buildInitialNotas(alunos, periodo))
  }, [alunos, periodo])

  const lancamentosUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/disciplinas/${disciplina?.id}/notas/create`

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div>
            <CardTitle>{disciplina?.sigla ?? "Disciplina"}</CardTitle>
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

            <Button asChild>
              <Link href={lancamentosUrl}>Lançar Notas</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={FileTextIcon}
              title="Nenhum lançamento realizado"
              description={`Nenhuma nota lançada para o ${periodo}º trimestre`}
              action={{
                label: "Lançar Notas",
                href: lancamentosUrl,
                variant: "outline",
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
                  const mediaFinal = aluno.notas?.[periodo]?.media_final ?? null
                  const faltasPeriodo = aluno.notas?.[periodo]?.faltas ?? 0
                  const situacao = verificarSituacao(mediaFinal, faltasPeriodo)

                  return (
                    <TableRow key={aluno.turma_aluno_id}>
                      <TableCell className="px-4">{index + 1}</TableCell>
                      <TableCell className="px-4">{aluno.nome}</TableCell>
                      <TableCell className="text-center">{n.mac ?? ""}</TableCell>
                      <TableCell className="text-center">{n.npp ?? ""}</TableCell>
                      <TableCell className="text-center">{n.npt ?? ""}</TableCell>
                      <TableCell className="text-center font-medium">{mt ?? "-"}</TableCell>
                      <TableCell className="text-center">{n.faltas ?? ""}</TableCell>
                      <TableCell className="text-end px-4">
                        {situacao === "APTO" && <Badge className="bg-green-50 text-green-500">APTO</Badge>}
                        {situacao === "N/APTO" && <Badge className="bg-red-50 text-red-500">NÃO APTO</Badge>}
                        {situacao === null && <span className="text-muted-foreground text-sm">-</span>}
                      </TableCell>
                    </TableRow>
                  )
                })}
              </TableBody>
            </Table>
          )}

          {!isEmpty && (
            <CardFooter className="justify-between">
              <span className="text-muted-foreground">
                {alunos.length} aluno{alunos.length !== 1 ? "s" : ""}
              </span>
            </CardFooter>
          )}
        </CardContent>
      </Card>
    </div>
  )
}