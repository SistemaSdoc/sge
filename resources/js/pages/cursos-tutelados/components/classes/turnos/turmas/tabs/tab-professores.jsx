"use client"
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from "@/components/ui/pagination";
import { Minus, MoreHorizontalIcon, BookOpenIcon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";
import Link from "next/link";

export function TabProfessores({
  data,
  cursoId,
  turmaId,
  instituicaoId,
  removerProfessorFn
}) {
  const router = useRouter()
  const isEmpty = !data?.professores || data.professores.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Professores a leccionar nesta turma</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/professores/create`}>
              Adicionar
            </Link>
          </Button>
        </CardAction>
      </CardHeader>
      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookOpenIcon}
            title="Nenhum professor na turma"
            description="Comece adicionando professores"
            action={{
              label: "Adicionar Professor",
              href: `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/professores/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Especialidade</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {data?.professores?.length > 0
                ? data.professores.map(prof => (
                  <TableRow
                    key={prof.id}
                    className="hover:cursor-pointer"
                    onClick={() => router.push(`/dashboard/professores/${prof.id}`)}
                  >
                    <TableCell className="px-4 font-medium">{prof.name}</TableCell>
                    <TableCell>{prof.email ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                    <TableCell>{prof.especialidade ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon" className="size-8">
                            <MoreHorizontalIcon />
                            <span className="sr-only">Open menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          <DropdownMenuItem variant="destructive" onClick={(e) => {
                            e.stopPropagation()
                            removerProfessorFn(prof.id)
                          }}>
                            Remover
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                ))
                : (
                  <TableRow>
                    <TableCell colSpan={4} className="px-4 py-6 text-center text-muted-foreground">
                      Nenhum professor associado a esta turma
                    </TableCell>
                  </TableRow>
                )
              }
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