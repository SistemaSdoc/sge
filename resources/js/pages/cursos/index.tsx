import { Link, router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { MoreHorizontalIcon, LayersIcon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";

import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table";

import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";
import classes from "../classes";

interface Curso {
  id: number;
  nome: string;
}

interface Props {
  cursos: Curso[];
}

export default function Index({ cursos }: Props) {
  const isEmpty = !cursos || cursos.length === 0;

  const excluir = (id: number) => {
    if (confirm("Tem certeza que deseja excluir esse curso?")) {
      router.delete(`/cursos/${id}`);
    }
  };

  return (
    <Card className="gap-0 w-full max-w-7xl mx-auto">
      <CardHeader className="border-b">
        <CardTitle>Cursos</CardTitle>
        <CardDescription>Lista de cursos cadastrados</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href="/cursos/create">Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum curso cadastrado"
            description="Comece adicionando a primeiro curso à tabela"
            action={{
              label: "Adicionar Curso",
              href: "/cursos/create",
              variant: "outline",
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {cursos.map((curso) => (
                <TableRow
                  key={curso.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(`/cursos/${curso.id}`)}
                >
                  <TableCell className="px-4 font-medium">{curso.nome}</TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Open menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(`/cursos/${curso.id}/edit`);
                          }}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            excluir(curso.id);
                          }}
                        >
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
  );
}