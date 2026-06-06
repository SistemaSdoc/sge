"use client"
import { useRouter } from "next/navigation";
import { Button } from "@/components/ui/button";
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { Minus, Users2Icon } from "lucide-react";
import { Pagination, PaginationContent, PaginationItem, PaginationNext, PaginationPrevious } from "@/components/ui/pagination";
import { EmptyState } from "@/components/empty-state";
import Link from "next/link";

export function TabGruposPAP({
  data,
  cursoId,
  classeId,
  turnoId,
  turmaId,
  instituicaoId,
}) {
  const router = useRouter()
  const isEmpty = !data?.grupos_pap || data.grupos_pap.length === 0;

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Grupos para PAP</CardTitle>
        <CardDescription>Grupos de aptidão profissional desta turma</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/grupos-pap/create`}>
              Criar grupo
            </Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users2Icon}
            title="Nenhum grupo para PAP"
            description="Comece adicionando grupos"
            action={{
              label: "Criar Grupo",
              href: `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/grupos-pap/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome do grupo</TableHead>
                <TableHead>Tema</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Nota final</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data?.grupos_pap?.length > 0
                ? data.grupos_pap.map(grupo => (
                  <TableRow
                    key={grupo.id}
                    className="hover:cursor-pointer"
                    onClick={(e) => {
                      e.stopPropagation()
                      router.push(`/dashboard/pap/grupos/${grupo.id}`)
                    }}
                  >
                    <TableCell className="px-4 font-medium">{grupo.nome_grupo}</TableCell>
                    <TableCell>{grupo.tema_grupo}</TableCell>
                    <TableCell>{grupo.status}</TableCell>
                    <TableCell>{grupo.nota_final ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  </TableRow>
                ))
                : (
                  <TableRow className="hover:bg-transparent">
                    <TableCell colSpan={6} className="px-4 py-6 text-center text-muted-foreground">
                      Nenhum grupo PAP criado nesta turma
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