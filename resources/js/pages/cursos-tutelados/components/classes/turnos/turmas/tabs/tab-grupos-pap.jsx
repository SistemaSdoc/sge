import { router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
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
import { Minus, Users2Icon } from "lucide-react";
import { EmptyState } from "@/components/empty-state";
import { Link } from "@inertiajs/react";

export function TabGruposPAP({
  turma,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
}) {
  const turmaId = turma.id;
  const grupos = turma.grupos_pap ?? [];
  const isEmpty = grupos.length === 0;

  const baseUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Grupos para PAP</CardTitle>
        <CardDescription>Grupos de aptidão profissional desta turma</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`${baseUrl}/pap/grupos/create`}>Criar grupo</Link>
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
              href: `${baseUrl}/pap/grupos/create`,
              variant: "outline",
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
              {grupos.map((grupo) => (
                <TableRow
                  key={grupo.id}
                  className="hover:cursor-pointer"
                  onClick={() => router.visit(`/pap/grupos/${grupo.id}`)}
                >
                  <TableCell className="px-4 font-medium">{grupo.nome_grupo}</TableCell>
                  <TableCell>{grupo.tema_grupo}</TableCell>
                  <TableCell>{grupo.status}</TableCell>
                  <TableCell>
                    {grupo.nota_final ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
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