import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
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
import { create as createHorarios } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoDisciplinaHorarioController';
import {
  create as createDisciplina,
  destroy,
} from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoDisciplinaController';
import { create as createNotas } from '@/actions/App/Http/Controllers/Tenant/NotaDisciplinaController';
import {
  create as createProfessor,
  destroy as destroyProfessor,
} from '@/actions/App/Http/Controllers/Tenant/InstituicaoCurso/TurmaDisciplinaProfessorController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { toast } from 'sonner';
import { useDialog } from '@/hooks/use-dialog';

export function TabDisciplinas({
  disciplinas,
  params,
  pagination,
  onPageChange,
  search = '',
  redirectTo,
  anoLectivoId,
  anoLectivoSelecionado,
  can = {},
}) {
  const {
    search: searchTerm,
    onChange,
    submit,
    applied,
  } = useTableSearch(search, {
    searchParam: 'search_disciplinas',
    pageParam: 'page_disciplinas',
  });
  const isEmpty = disciplinas.length === 0;
  const canCreate = Boolean(can.create);
  const { deleteConfirm } = useDialog();

  const abrirHorarios = (disciplina, e) => {
    e.stopPropagation();

    router.visit(
      createHorarios(
        {
          instituicao: params.instituicao.id,
          cursoTutelado: params.cursoTutelado.id,
          cursoClasse: params.cursoClasse.id,
          cursoClasseTurno: params.cursoClasseTurno.id,
          turma: params.turma,
          classeTurnoDisciplina: disciplina.id,
        },
        { query: { ano_lectivo_id: anoLectivoId } },
      ).url,
    );
  };

  const handleDeleteDisciplina = (disciplinaId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'Esta disciplina será removida desta turma.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroy[
            '/dashboard/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/classes/{cursoClasse}/turnos/{cursoClasseTurno}/disciplinas/{classeTurnoDisciplina}'
          ]({
            instituicao: params.instituicao.id,
            cursoTutelado: params.cursoTutelado.id,
            cursoClasse: params.cursoClasse.id,
            cursoClasseTurno: params.cursoClasseTurno.id,
            classeTurnoDisciplina: disciplinaId,
          }).url,
          {
            preserveScroll: true,
            data: { ano_lectivo_id: anoLectivoSelecionado },
            onSuccess: () => toast.success('Disciplina removida com sucesso'),
            onError: (errors) => toast.error(errors.message),
          },
        ),
    });
  };

  const handleRemoveProfessor = (disciplina) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'O professor será desassociado desta disciplina na turma.',
      confirmLabel: 'Desassociar',
      confirmFn: () =>
        router.delete(
          destroyProfessor({
            instituicao: params.instituicao.id,
            cursoTutelado: params.cursoTutelado.id,
            cursoClasse: params.cursoClasse.id,
            cursoClasseTurno: params.cursoClasseTurno.id,
            turma: params.turma,
            classeTurnoDisciplina: disciplina.id,
          }).url,
          {
            preserveScroll: true,
            data: { ano_lectivo_id: anoLectivoSelecionado },
            onSuccess: () =>
              toast.success('Professor desassociado com sucesso'),
            onError: (errors) => toast.error(errors.message),
          },
        ),
    });
  };

  const handleDisciplinaClick = (disciplina) => {
    if (disciplina?.arquivada) {
      toast.warning('Esta disciplina está arquivada.');
      return;
    }

    if (!disciplina.professor) {
      toast.warning('Esta disciplina ainda não tem professor atribuído.');
      return;
    }

    if (disciplina?.can?.view) {
      router.visit(
        createNotas(
          {
            instituicao: params.instituicao.id,
            cursoTutelado: params.cursoTutelado.id,
            cursoClasse: params.cursoClasse.id,
            cursoClasseTurno: params.cursoClasseTurno.id,
            turma: params.turma,
            classeTurnoDisciplina: disciplina.id,
          },
          { query: { ano_lectivo_id: anoLectivoId } },
        ).url,
      );
    }
  };

  return (
    <Card className="grid w-full min-w-0 grid-rows-[auto_1fr_auto] gap-0">
      <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <CardTitle>Disciplinas</CardTitle>
          <CardDescription>Disciplinas lecionadas nesta turma</CardDescription>
        </div>
        {canCreate && (
          <div className="w-full sm:w-auto">
            <Button asChild className="w-full sm:w-auto">
              <Link
                data={{ redirect_to: window.location.href }}
                href={
                  createDisciplina(
                    {
                      instituicao: params.instituicao.id,
                      cursoTutelado: params.cursoTutelado.id,
                      cursoClasse: params.cursoClasse.id,
                      cursoClasseTurno: params.cursoClasseTurno,
                    },
                    { query: { ano_lectivo_id: anoLectivoId } },
                  ).url
                }
              >
                Adicionar Disciplinas
              </Link>
            </Button>
          </div>
        )}
      </CardHeader>

      <CardContent className="min-w-0 p-0!">
        <TableSearch
          value={searchTerm}
          onChange={onChange}
          onSubmit={submit}
          placeholder="Pesquisar por disciplina ou sigla..."
        />
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title={
              applied
                ? 'Nenhuma disciplina encontrada'
                : 'Nenhuma disciplina nesta turma'
            }
            description={
              applied
                ? 'Tente ajustar a pesquisa.'
                : 'Comece adicionando disciplinas'
            }
            action={{
              label: 'Adicionar Disciplina',
              onClick: () =>
                router.visit(
                  createDisciplina(
                    {
                      instituicao: params.instituicao.id,
                      cursoTutelado: params.cursoTutelado.id,
                      cursoClasse: params.cursoClasse.id,
                      cursoClasseTurno: params.cursoClasseTurno,
                    },
                    { query: { ano_lectivo_id: anoLectivoId } },
                  ).url,
                  { data: { redirect_to: redirectTo } },
                ),
              variant: 'outline',
            }}
          />
        ) : (
          <Table className="min-w-0">
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4 max-md:whitespace-normal">
                  Nome
                </TableHead>
                <TableHead className="max-md:whitespace-normal">
                  Professor
                </TableHead>
                <TableHead className="px-4 text-right max-md:whitespace-normal">
                  Acções
                </TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {disciplinas.map((disciplina) => (
                <TableRow
                  key={disciplina.id}
                  aria-disabled={
                    disciplina?.arquivada || !disciplina?.can?.view
                  }
                  className="hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-60 aria-disabled:hover:bg-transparent"
                  onClick={() => handleDisciplinaClick(disciplina)}
                >
                  <TableCell className="px-2 font-medium max-md:whitespace-normal sm:px-4">
                    <span className="wrap-anywhere">{disciplina?.nome}</span>
                    {disciplina?.arquivada && (
                      <span className="ml-2 rounded-full bg-muted px-2 py-0.5 text-[10px] text-muted-foreground">
                        Arquivada
                      </span>
                    )}
                  </TableCell>

                  <TableCell className="wrap-anywhere max-md:whitespace-normal">
                    {disciplina.professor?.nome ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell className="px-2 text-right max-md:whitespace-normal sm:px-4">
                    {!disciplina?.arquivada && (
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="outline"
                            size="icon"
                            className="size-8"
                          >
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="w-auto">
                          {disciplina?.can?.assign_professor && (
                            <DropdownMenuItem
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  createProfessor(
                                    {
                                      instituicao: params.instituicao.id,
                                      cursoTutelado: params.cursoTutelado.id,
                                      cursoClasse: params.cursoClasse.id,
                                      cursoClasseTurno:
                                        params.cursoClasseTurno.id,
                                      turma: params.turma,
                                      classeTurnoDisciplina: disciplina.id,
                                    },
                                    { query: { ano_lectivo_id: anoLectivoId } },
                                  ).url,
                                );
                              }}
                            >
                              Definir professor
                            </DropdownMenuItem>
                          )}

                          <DropdownMenuItem
                            onClick={(e) => abrirHorarios(disciplina, e)}
                          >
                            {disciplina?.can?.manage_schedule
                              ? 'Definir horários'
                              : 'Visualizar horários'}
                          </DropdownMenuItem>

                          {disciplina.professor &&
                            disciplina?.can?.detach_professor && (
                              <DropdownMenuItem
                                variant="destructive"
                                onClick={(e) => {
                                  e.stopPropagation();
                                  handleRemoveProfessor(disciplina);
                                }}
                              >
                                Desassociar professor
                              </DropdownMenuItem>
                            )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    )}
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
