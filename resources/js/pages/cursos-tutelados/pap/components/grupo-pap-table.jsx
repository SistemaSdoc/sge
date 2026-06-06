"use client"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { MoreHorizontalIcon, Minus, UsersIcon } from "lucide-react"
import { EmptyState } from "@/components/empty-state"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from "@/components/ui/pagination"
import Link from "next/link"

export function GrupoPapTable({ grupos = [], deleteFn, createHref }) {
  const router = useRouter()
  const isEmpty = !grupos || grupos.length === 0

  return (
    <Card className="gap-0 w-full max-w-7xl mx-auto">
      <CardHeader className="border-b">
        <CardTitle>Grupos PAP</CardTitle>
        <CardDescription>Lista de grupos de aptidão profissional</CardDescription>
        {createHref && (
          <CardAction>
            <Button asChild>
              <Link href={createHref}>Criar grupo</Link>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum grupo PAP cadastrado"
            description="Comece adicionando o primeiro grupo PAP à tabela"
            action={{
              label: "Criar Grupo",
              href: "/dashboard/pap/create",
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome do grupo</TableHead>
                <TableHead>Tema</TableHead>
                <TableHead>Professor tutor</TableHead>
                <TableHead>Turma</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Nota final</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {grupos.map(grupo => (
                <TableRow
                  key={grupo.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.push(`/dashboard/pap/grupos/${grupo.id}`)}
                >
                  <TableCell className="px-4 font-medium">{grupo.nome_grupo}</TableCell>
                  <TableCell>{grupo.tema_grupo}</TableCell>
                  <TableCell>{grupo.professor ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>{grupo.turma ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>{grupo.status}</TableCell>
                  <TableCell>{grupo.nota_final ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem onClick={(e) => {
                          e.stopPropagation()
                          router.push(`/dashboard/pap/grupos/${grupo.id}`)
                        }}>
                          Ver grupo
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
                        <DropdownMenuItem variant="destructive" onClick={(e) => {
                          e.stopPropagation()
                          deleteFn(grupo.id)
                        }}>
                          Remover
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      {!isEmpty && (
        <CardFooter className="justify-between">
          <span className="text-muted-foreground">Página 1 de 1</span>
          <Pagination>
            <PaginationContent>
              <PaginationItem><PaginationPrevious href="#" /></PaginationItem>
              <PaginationItem><PaginationNext href="#" /></PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      )}
    </Card>
  )
}