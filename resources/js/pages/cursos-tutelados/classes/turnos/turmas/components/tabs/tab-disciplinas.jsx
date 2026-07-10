import { Link, router } from '@inertiajs/react';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Minus, MoreHorizontalIcon, BookIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { HorariosForm } from '../horarios/horarios-form';
import { useDialog } from '@/hooks/use-dialog';
import { store as storeHorario } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaHorarioController';
import { create as createDisciplina } from '@/actions/App/Http/Controllers/ClasseTurnoDisciplinaController';
import { index } from '@/actions/App/Http/Controllers/NotaDisciplinaController';
import { create as createProfessor } from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController';
import TablePagination from '@/components/table-pagination';
import { toast } from 'sonner';

export function TabDisciplinas({
  disciplinas,
  params,
  pagination,
  onPageChange,
  redirectTo, // <-- adiciona
  can = {},
}) {
  const isEmpty = disciplinas.length === 0;
  const canCreate = Boolean(can.create);
  const canAssignProfessor = Boolean(can.assign_professor);
  const { openForm, closeDialog } = useDialog();

  const abrirHorariosDialog = (disciplina, e) => {
    e.stopPropagation();

    const action = storeHorario.form({
      ...params,
      classeTurnoDisciplina: disciplina.id,
    }).action;

    openForm({
      title: `Horários de ${disciplina?.nome}`,
      description: 'Configure os horários de aulas para esta disciplina',
      content: (
        <HorariosForm
          defaultValues={disciplina?.horarios}
          onSubmit={(payload) => {
            router.post(
              action,
              { horarios: payload },
              {
                onSuccess: () => closeDialog(),
              },
            );
          }}
        />
      ),
    });
  };

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Disciplinas</CardTitle>
        <CardDescription>Disciplinas lecionadas nesta turma</CardDescription>
        {canCreate && (
          <CardAction>
            <Button asChild size="sm">
              <Link
                data={{ redirect_to: window.location.href }}
                href={createDisciplina(params).url}
              >
                Adicionar
              </Link>
            </Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhuma disciplina nesta turma"
            description="Comece adicionando disciplinas"
            action={{
              label: 'Adicionar Disciplina',
              onClick: () =>
                router.visit(createDisciplina(params).url, {
                  data: { redirect_to: redirectTo },
                }),
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Professor</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {disciplinas.map((disciplina) => {
                return (
                  <TableRow
                    key={disciplina.id}
                    className="hover:cursor-pointer"
                    onClick={() => {
                      if (!disciplina.professor) {
                        toast.warning(
                          'Esta disciplina ainda não tem professor atribuído.',
                        );
                        return;
                      }
                      router.visit(
                        index({
                          ...params,
                          classeTurnoDisciplina: disciplina.id,
                        }).url,
                      );
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {disciplina?.nome}
                    </TableCell>

                    <TableCell>
                      {disciplina.professor?.nome ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                          >
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="w-auto">
                          {canAssignProfessor && (
                            <DropdownMenuItem
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  createProfessor({
                                    ...params,
                                    classeTurnoDisciplina: disciplina.id,
                                  }).url,
                                );
                              }}
                            >
                              Definir professor
                            </DropdownMenuItem>
                          )}

                          <DropdownMenuItem
                            onClick={(e) => abrirHorariosDialog(disciplina, e)}
                          >
                            Definir horários
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
