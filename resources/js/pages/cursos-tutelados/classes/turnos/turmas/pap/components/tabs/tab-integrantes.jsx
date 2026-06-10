"use client"
import Link from "next/link"
import { useState } from "react"
import { Edit3, Minus, MoreHorizontalIcon, Pencil, UsersIcon } from "lucide-react"
import { Input } from "@/components/ui/input"
import { Button } from "@/components/ui/button"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { useRouter } from "next/navigation"
import { usePermissions } from "@/features/auth/hooks/usePermissions"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { EmptyState } from "@/components/empty-state"


export function TabIntegrantes({ id, data, notas, setNotas, deleteFn, actualizarNotaFn }) {
  const router = useRouter()
  const { hasPermission } = usePermissions()
  const isEmpty = !data?.elementos || data.elementos.length === 0

  const canEditNotas = true // placeholder — troca pela vossa função
  const [editando, setEditando] = useState({})

  function handleSalvar(el) {
    actualizarNotaFn(
      {
        elementoId: el.id,
        data: { nota_individual: Number(notas[el.id] ?? el.nota_individual) }
      },
      {
        onSuccess: () => {
          // ao salvar com sucesso, sai do modo edição desta linha
          setEditando(prev => ({ ...prev, [el.id]: false }))
        }
      }
    )
  }

  function notaJaLancada(el) {
    // nota lançada = tem valor E não está em modo edição agora
    return el.nota_individual !== null
      && el.nota_individual !== undefined
      && !editando[el.id]
  }

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Integrantes do grupo</CardTitle>
        <CardDescription>Alunos membros e notas individuais</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`/dashboard/pap/grupos/${id}/integrantes/create`}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum integrante no grupo"
            description="Comece adicionando os primeiros membros do grupo PAP"
            action={{
              label: "Adicionar Integrante",
              href: `/dashboard/pap/grupos/${id}/integrantes/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Matrícula</TableHead>
                <TableHead>Nota individual</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.elementos.map(el => (
                <TableRow
                  key={el.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.push(`/dashboard/alunos/${el.aluno_id}`)}
                >
                  <TableCell className="px-4 font-medium">{el.nome}</TableCell>
                  <TableCell>
                    {el.matricula ?? <Minus size={15} className="text-muted-foreground" />}
                  </TableCell>

                  <TableCell onClick={e => e.stopPropagation()}>
                    {notaJaLancada(el) ? (
                      <span className="text-sm font-medium tabular-nums">
                        {Number(el.nota_individual).toFixed(2)}
                      </span>
                    ) : (
                      // ainda sem nota (ou em modo edição) — mostra input + botão salvar
                      <div className="flex items-center gap-2">
                        <Input
                          type="number"
                          min="0"
                          max="20"
                          step="0.5"
                          className="w-20"
                          defaultValue={el.nota_individual ?? ""}
                          onChange={e =>
                            setNotas(prev => ({ ...prev, [el.id]: e.target.value }))
                          }
                        />
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => handleSalvar(el)}
                        >
                          Salvar
                        </Button>
                      </div>
                    )}
                  </TableCell>

                  <TableCell className="px-4 text-right" onClick={e => e.stopPropagation()}>
                    {notaJaLancada(el) && hasPermission('pap.edit') && (
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="size-8">
                            <MoreHorizontalIcon />
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem onClick={() => setEditando(prev => ({ ...prev, [el.id]: true }))}>
                            Editar nota
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  )
}