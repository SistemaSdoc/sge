import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Minus, Users2Icon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Link } from '@inertiajs/react';
import { usePagination } from '@/hooks/use-pagination';
import TablePagination from '@/components/table-pagination';
//import {create} from '@/routes/cursos-tutelados/classes/turnos/turmas/disciplinas/professores'
//import {create} from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController'

export function TabGruposPAP({
  turma,
  grupos,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
  pagination,
  onPageChange,
  can,
}) {
  const turmaId = turma.id;
  const isEmpty = grupos.length === 0;

  const baseUrl = `/dashboard/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Grupos para PAP</CardTitle>
        <CardDescription>
          Grupos de aptidão profissional desta turma
        </CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
      
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
                  onClick={() => router.visit(`${baseUrl}/pap/${grupo.id}`)}
                >
                  <TableCell className="px-4 font-medium">
                    {grupo.nome}
                  </TableCell>
                  <TableCell>{grupo.tema}</TableCell>
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
      </CardContent>
      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
