"use client"
import { useState, useEffect } from "react"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Input } from "@/components/ui/input"
import { Badge } from "@/components/ui/badge"
import { Loader2, ClipboardListIcon } from "lucide-react"
import { EmptyState } from "@/components/empty-state"

function buildInitialNotas(alunos) {
    const state = {}
    for (const aluno of alunos) {
        state[aluno.aluno_id] = {}
        for (const [sigla, nota] of Object.entries(aluno.notas ?? {})) {
            state[aluno.aluno_id][sigla] = {
                nota_recurso: nota.recurso ?? "",
            }
        }
    }
    return state
}

function SituacaoBadge({ valor }) {
    if (valor === "" || valor === null || valor === undefined)
        return <span className="text-muted-foreground text-sm">—</span>

    const aprovado = Number(valor) >= 10

    return aprovado
        ? <Badge className="bg-green-50 text-green-600 border-green-200">Aprovado</Badge>
        : <Badge className="bg-red-50 text-destructive border-red-200">Reprovado</Badge>
}

export default function LancamentosRecursoTable({ alunos = [], onSubmit, isPending }) {
    const [notas, setNotas] = useState({})
    const isEmpty = alunos.length === 0

    useEffect(() => {
        if (alunos.length > 0) {
            setNotas(buildInitialNotas(alunos))
        }
    }, [alunos])

    function handleChange(alunoId, sigla, valor) {
        setNotas(prev => ({
            ...prev,
            [alunoId]: {
                ...prev[alunoId],
                [sigla]: { nota_recurso: valor },
            },
        }))
    }

    function handleSubmit() {
        const lancamentos = []

        for (const aluno of alunos) {
            const notasAluno = notas[aluno.aluno_id] ?? {}

            for (const [sigla, valores] of Object.entries(notasAluno)) {
                const tdpId = aluno.notas?.[sigla]?.tdp_id ?? null
                if (!tdpId) continue

                lancamentos.push({
                    turma_aluno_id: aluno.turma_aluno_id,
                    tdp_id: tdpId,
                    nota_recurso: valores.nota_recurso !== "" ? Number(valores.nota_recurso) : null,
                })
            }
        }

        onSubmit({ periodo: 4, lancamentos })
    }

    return (
        <Card className="gap-0">
            <CardHeader className="border-b">
                <div>
                    <CardTitle>Lançamento de Recurso</CardTitle>
                    <CardDescription>
                        Apenas disciplinas negativas. Uma nota directa por disciplina.
                    </CardDescription>
                </div>
                <CardAction>
                    <Button onClick={handleSubmit} disabled={isPending}>
                        {isPending && <Loader2 className="animate-spin size-4 mr-2" />}
                        Lançar Recurso
                    </Button>
                </CardAction>
            </CardHeader>

            <CardContent className="p-0!">
                {isEmpty ? (
                    <EmptyState
                        variant="table"
                        icon={ClipboardListIcon}
                        title="Nenhum aluno em recurso"
                        description="Nenhum aluno foi identificado em situação de recurso."
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/70">
                                <TableHead className="px-4 w-10">#</TableHead>
                                <TableHead className="px-4">Aluno</TableHead>
                                <TableHead className="px-4 text-center">Disciplina</TableHead>
                                <TableHead className="px-4 text-center">MF (P3)</TableHead>
                                <TableHead className="px-4 text-center">Nota Recurso</TableHead>
                                <TableHead className="px-4 text-end">Resultado</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {alunos.map((aluno, index) => {
                                const disciplinas = Object.entries(aluno.notas ?? {})
                                const rowSpan = disciplinas.length

                                return disciplinas.map(([sigla, nota], discIndex) => {
                                    const valorActual = notas[aluno.aluno_id]?.[sigla]?.nota_recurso ?? ""

                                    return (
                                        <TableRow key={`${aluno.aluno_id}-${sigla}`}>
                                            {discIndex === 0 && (
                                                <>
                                                    <TableCell className="px-4 text-muted-foreground" rowSpan={rowSpan}>
                                                        {index + 1}
                                                    </TableCell>
                                                    <TableCell className="px-4 font-medium" rowSpan={rowSpan}>
                                                        {aluno.nome}
                                                    </TableCell>
                                                </>
                                            )}

                                            <TableCell className="px-4 text-center text-sm">
                                                {sigla}
                                            </TableCell>

                                            <TableCell className="px-4 text-center">
                                                <span className="text-destructive font-medium">
                                                    {nota.mf !== null ? Number(nota.mf) : "—"}
                                                </span>
                                            </TableCell>

                                            <TableCell className="px-4 text-center">
                                                <Input
                                                    type="number"
                                                    min={0}
                                                    max={20}
                                                    value={valorActual}
                                                    onChange={e => handleChange(aluno.aluno_id, sigla, e.target.value)}
                                                    className="text-center w-24 mx-auto"
                                                    placeholder="0 - 20"
                                                />
                                            </TableCell>

                                            <TableCell className="px-4 text-end">
                                                <SituacaoBadge valor={valorActual} />
                                            </TableCell>
                                        </TableRow>
                                    )
                                })
                            })}
                        </TableBody>
                    </Table>
                )}

                {!isEmpty && (
                    <CardFooter className="border-t pt-4">
                        <span className="text-muted-foreground text-sm">
                            {alunos.length} aluno{alunos.length !== 1 ? "s" : ""} em recurso
                        </span>
                    </CardFooter>
                )}
            </CardContent>
        </Card>
    )
}