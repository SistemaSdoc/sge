import { router, Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import {
  Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle,
} from "@/components/ui/card";
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from "@/components/ui/table";
import {
  DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { Minus, MoreHorizontalIcon, BookOpenIcon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";

export function TabProfessores({
  turma,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
}) {
  const turmaId = turma.id;
  const baseUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  // professores únicos desta turma via turmaDisciplinaProfessor
  const professores = turma.turma_disciplina_professor
    ?.map((tdp) => tdp.professor)
    .filter(Boolean)
    .filter((p, i, arr) => arr.findIndex((x) => x.id === p.id) === i) ?? [];

  const isEmpty = professores.length === 0;

  const remover = (e, professorId) => {
    e.stopPropagation();
    router.delete(`${baseUrl}/professores/${professorId}`, {
      preserveScroll: true,
    });
  };

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Professores a leccionar nesta turma</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`${baseUrl}/professores/create`}>Adicionar</Link>
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
            action={{ label: "Adicionar Professor", href: `${baseUrl}/professores/create`, variant: "outline" }}
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
              {professores.map((prof) => (
                <TableRow
                  key={prof.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(`/professores/${prof.id}`)}
                >
                  <TableCell className="px-4 font-medium">{prof.user?.nome}</TableCell>
                  <TableCell>{prof.user?.email ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell>{prof.especialidade ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Abrir menu</span>
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem variant="destructive" onClick={(e) => remover(e, prof.id)}>
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
    </Card>
  );
}