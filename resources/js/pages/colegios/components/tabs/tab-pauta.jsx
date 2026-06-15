import { useState } from "react"
import { router } from "@inertiajs/react"
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"

export function TabPauta({ pauta, cursoTuteladoId, turmaId }) {
  const [periodo, setPeriodo] = useState(undefined)

  const handlePeriodo = (val) => {
    const novoPeriodo = val === "final" ? undefined : val
    setPeriodo(novoPeriodo)
    router.reload({
      data: { periodo: novoPeriodo },
      only: ['cursoTutelado'],
      preserveState: true,
    })
  }

  return (
    <Card className="gap-0">
      <CardHeader className="border-b flex flex-row items-center justify-between">
        <CardTitle>Pauta</CardTitle>
        <Select onValueChange={handlePeriodo}>
          <SelectTrigger className="w-40">
            <SelectValue placeholder="Período" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="1">1º Trimestre</SelectItem>
            <SelectItem value="2">2º Trimestre</SelectItem>
            <SelectItem value="3">3º Trimestre</SelectItem>
            <SelectItem value="final">Final</SelectItem>
          </SelectContent>
        </Select>
      </CardHeader>
      <CardContent className="p-0!">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/72">
              <TableHead className="px-4">Nº</TableHead>
              <TableHead>Nome</TableHead>
              {pauta?.disciplinas?.map(disc => (
                <TableHead key={disc}>{disc}</TableHead>
              ))}
              <TableHead>Faltas</TableHead>
              <TableHead>Resultado</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {pauta?.alunos?.map(aluno => (
              <TableRow key={aluno.aluno_id}>
                <TableCell className="px-4">{aluno.numero}</TableCell>
                <TableCell>{aluno.nome}</TableCell>
                {pauta?.disciplinas?.map(disc => (
                  <TableCell key={disc}>
                    {(() => {
                      const nota = aluno.notas[disc]
                      if (!nota) return "—"
                      if (nota.media !== undefined)
                        return nota.media !== null ? Number(nota.media).toFixed(1) : "—"
                      if (nota.mf !== undefined)
                        return nota.mf !== null ? Number(nota.mf).toFixed(1) : "—"
                      return "—"
                    })()}
                  </TableCell>
                ))}
                <TableCell>{aluno.total_faltas}</TableCell>
                <TableCell>{aluno.resultado}</TableCell>
              </TableRow>
            ))}
          </TableBody>
        </Table>
      </CardContent>
    </Card>
  )
}