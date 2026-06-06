"use client"
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from "@/components/ui/pagination";
import axios from "@/lib/axios";

import { Minus, MoreHorizontalIcon, UsersIcon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";
import Link from "next/link";
export function TabAlunos({
  data,

  instituicaoId,
  cursoId,
  turmaId,
  classeId,    // ← ADICIONAR
  turnoId,
}) {
  const router = useRouter()
  const isEmpty = !data?.alunos || data.alunos.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Alunos</CardTitle>
        <CardDescription>Alunos inscritos nesta turma</CardDescription>

        <CardAction>
          <Button asChild>
            <Link href={`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/alunos/create`}>
              Adicionar
            </Link>
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum aluno inscrito"
            description="Comece adicionando alunos à turma"
            action={{
              label: "Adicionar Aluno",
              href: `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/alunos/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Matrícula</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Telefone</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {data?.alunos?.map(aluno => (
                <TableRow
                  key={aluno.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.push(`/dashboard/alunos/${aluno.id}`)}
                >
                  <TableCell className="px-4 font-medium">{aluno.nome}</TableCell>
                  <TableCell>{aluno.matricula ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>{aluno.email ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>{aluno.telefone ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={async (e) => {
                            e.stopPropagation()

                            try {
                              const response = await axios.get(
                                `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/alunos/${aluno.id}/certificado`,
                                {
                                  responseType: "blob",
                                }
                              )

                              const url = window.URL.createObjectURL(new Blob([response.data]))
                              const link = document.createElement("a")
                              link.href = url
                              link.setAttribute("download", "certificado.pdf")

                              document.body.appendChild(link)
                              link.click()

                              link.remove()
                              window.URL.revokeObjectURL(url)
                            } catch (error) {
                              console.error("Erro ao gerar certificado:", error)
                            }
                          }}
                        >
                          Gerar Certificado
                        </DropdownMenuItem>
                        <DropdownMenuSeparator />
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
          <span className="text-muted-foreground">Página 1 de 4</span>

          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious href="#" />
              </PaginationItem>

              <PaginationItem>
                <PaginationNext href="#" />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      )}
    </Card>
  )
}