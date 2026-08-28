import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { EmptyState } from '@/components/empty-state';
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { ArrowLeftIcon, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { router } from '@inertiajs/react';
import { show as showCurso } from '@/actions/App/Http/Controllers/Tenant/Colegios/CursoTuteladoController';
import { show as showTurma } from '@/actions/App/Http/Controllers/Tenant/Colegios/ClasseTurnoTurmaController';
import TablePagination from '@/components/table-pagination';
import { useRef, useState } from 'react';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  colegio,
  anosLectivos = [],
  anoLectivoActual,
}) {
  const instituicaoId = instituicao.id;
  const cursoId = cursoTutelado.id;
  const classeId = cursoClasse.id;
  const turnos = cursoClasse.turnos || [];
  const turmas = cursoClasse.turmas;
  const selectedTurnoId = cursoClasse.turnoId;
  const [anoLectivoSelecionado, setAnoLectivoSelecionado] =
    useState(anoLectivoActual);
  const lastTurnoRef = useRef(null);

  const handleTurnoChange = (turnoId) => {
    if (lastTurnoRef.current === turnoId) {
      return;
    }

    lastTurnoRef.current = turnoId;

    router.get(
      window.location.pathname,
      {
        instituicao: instituicaoId,
        turno: turnoId,
        ano_lectivo_id: anoLectivoSelecionado,
        page_turmas: 1,
        page_disciplinas: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
          lastTurnoRef.current = null;
        },
      },
    );
  };

  const handlePageChange = (param) => (page) => {
    router.get(
      window.location.pathname,
      {
        instituicao: instituicaoId,
        turno: selectedTurnoId,
        ano_lectivo_id: anoLectivoSelecionado,
        [param]: page,
      },
      { preserveState: true, preserveScroll: true },
    );
  };

  const handleAnoLectivoChange = (value) => {
    setAnoLectivoSelecionado(value);

    router.get(
      window.location.pathname,
      {
        instituicao: instituicaoId,
        turno: selectedTurnoId,
        ano_lectivo_id: value,
        page_turmas: 1,
        page_disciplinas: 1,
      },
      {
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      <Card className="gap-0">
        <CardHeader>
          <CardTitle className="text-xl">{cursoClasse.classe?.nome}</CardTitle>
          <CardDescription>
            Gerir disciplinas e turmas por turno
          </CardDescription>
          {/*
          
            <CardAction>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button variant="ghost" size="icon">
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-auto!">
                <DropdownMenuItem
                  onClick={() =>
                    router.visit(
                      showCurso({
                        colegio: colegio.id,
                        cursoTutelado: cursoId,
                      }).url,
                    )
                  }
                                }, {
                                  query: {
                                    instituicao: instituicaoId,
                                  },
                >
                  <ArrowLeftIcon strokeWidth={1.5} /> Voltar ao curso
                </DropdownMenuItem>
                <DropdownMenuSeparator />
              </DropdownMenuContent>
            </DropdownMenu>
          </CardAction>
          
          */}
        </CardHeader>
      </Card>
      <Tabs value={selectedTurnoId} onValueChange={handleTurnoChange}>
        <TabsList variant={'line'} className="flex! w-full justify-between">
          <div>
            {turnos.map((turno) => (
              <TabsTrigger key={turno.id} value={turno.id}>
                {turno.nome}
              </TabsTrigger>
            ))}
          </div>

          <div>
            <Select
              value={anoLectivoSelecionado ?? ''}
              onValueChange={handleAnoLectivoChange}
            >
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Anos Lectivos</SelectLabel>
                  {anosLectivos.map((ano) => (
                    <SelectItem key={ano?.id} value={ano?.id}>
                      {ano?.nome}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </TabsList>

        <TabsContent value={selectedTurnoId} className="mt-2 space-y-6">
          <Card className="flex flex-col gap-0">
            <CardHeader className="border-b">
              <CardTitle className="flex! gap-2">
                <UsersIcon className="size-5 text-primary" />
                Turmas ({turmas?.total ?? 0})
              </CardTitle>
              <CardDescription>Turmas do turno selecionado</CardDescription>
            </CardHeader>
            {!turmas?.data?.length ? (
              <CardContent className="flex flex-1 items-center justify-center">
                <EmptyState
                  variant="table"
                  icon={UsersIcon}
                  title="Nenhuma turma"
                  description="Este turno ainda não tem turmas criadas"
                />
              </CardContent>
            ) : (
              <>
                <CardContent className="p-0!">
                  <Table>
                    <TableHeader>
                      <TableRow className="bg-muted/72">
                        <TableHead className="px-4">Nome</TableHead>
                        <TableHead>Alunos</TableHead>
                      </TableRow>
                    </TableHeader>
                    <TableBody>
                      {turmas.data.map((turma) => (
                        <TableRow
                          key={turma.id}
                          aria-disabled={!turma.can?.view}
                          className="hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-70 aria-disabled:hover:bg-transparent"
                          onClick={() => {
                            console.log('showTurma params:', {
                              instituicao: instituicaoId,
                              colegio: colegio.id,
                              cursoTutelado: cursoId,
                              cursoClasse: classeId,
                              cursoClasseTurno: selectedTurnoId,
                              turma: turma.id,
                            });
                            if (turma.can?.view) {
                              router.visit(
                                showTurma({
                                  colegio: colegio.id,
                                  cursoTutelado: cursoId,
                                  cursoClasse: classeId,
                                  cursoClasseTurno: selectedTurnoId,
                                  turma: turma.id,
                                }, {
                                  query: {
                                    instituicao: instituicaoId,
                                  },
                                }).url,
                              );
                            }
                          }}
                        >
                          <TableCell className="px-4 font-medium">
                            {turma.nome}
                          </TableCell>
                          <TableCell>{turma.alunos_activos_count}</TableCell>
                        </TableRow>
                      ))}
                    </TableBody>
                  </Table>
                </CardContent>
                <TablePagination
                  pagination={{
                    current_page: turmas.current_page,
                    last_page: turmas.last_page,
                  }}
                  onPageChange={handlePageChange('page_turmas')}
                />
              </>
            )}
          </Card>
        </TabsContent>
      </Tabs>
    </div>
  );
}
