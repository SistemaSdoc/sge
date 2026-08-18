import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
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
import {
  create,
  show,
} from '@/actions/App/Http/Controllers/GrupoPapController';
//import {create} from '@/routes/cursos-tutelados/classes/turnos/turmas/disciplinas/professores'
//import {create} from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController'

export function TabGruposPAP({
  turma,
  grupos,
  pagination,
  onPageChange,
  can,
  params,
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
        {can?.create && (
          <CardAction>
            <Button asChild>
              <Link
                href={create({
                  instituicao: params.instituicao.id,
                  cursoTutelado: params.cursoTutelado.id,
                  cursoClasse: params.cursoClasse.id,
                  cursoClasseTurno: params.cursoClasseTurno.id,
                  turma: params.turma,
                })}
              >
                Criar grupo
              </Link>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users2Icon}
            title="Nenhum grupo criado, ainda"
            description="Comece adicionando grupos"
            action={
              can?.create
                ? {
                    label: 'Criar Grupo',
                    href: create({
                      instituicao: params.instituicao.id,
                      cursoTutelado: params.cursoTutelado.id,
                      cursoClasse: params.cursoClasse.id,
                      cursoClasseTurno: params.cursoClasseTurno.id,
                      turma: params.turma,
                    }),
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Tema</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead className="px-4 text-end">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {grupos.map((grupo) => (
                <TableRow
                  key={grupo.id}
                  className="hover:cursor-pointer"
                  onClick={() =>
                    router.visit(
                      show({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                        cursoClasse: params.cursoClasse.id,
                        cursoClasseTurno: params.cursoClasseTurno.id,
                        turma: params.turma,
                        grupoPap: grupo.id,
                      }),
                    )
                  }
                >
                  <TableCell className="px-4 font-medium">
                    {grupo.nome}
                  </TableCell>

                  <TableCell>{grupo.tema}</TableCell>

                  <TableCell>{grupo.status}</TableCell>

                  <TableCell className="text-end">
                    <Button variant="outline" size="xs" className="text-[10px]">
                      Ver detalhes
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
