import { Link, router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Minus, MoreHorizontalIcon } from 'lucide-react';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { TabTurmas } from './components/tabs/tab-turmas';
import { TabProfessores } from './components/tabs/tab-professores';
import { Badge } from '@/components/ui/badge';
import { show as showClasse } from '@/actions/App/Http/Controllers/CursoClasseController';
import {
  edit,
  show as showCurso,
} from '@/actions/App/Http/Controllers/CursoTuteladoController';
import { destroy } from '@/actions/App/Http/Controllers/CursoTuteladoProfessorController';
import { useDialog } from '@/hooks/use-dialog';
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
  cursoTutelado,
  anoLectivoId,
  anosLectivos = [],
}) {
  const instituicaoId = cursoTutelado.instituicao.id;
  const cursoTuteladoId = cursoTutelado.id;
  const cursoId = cursoTutelado.curso.id;
  const { deleteConfirm } = useDialog();

  const hasAnyAction = cursoTutelado.can?.update || cursoTutelado.can?.delete;

  const handleDelete = (vinculoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O professor será removido do curso.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroy({
            instituicao: instituicaoId,
            cursoTutelado: cursoTuteladoId,
            professore: vinculoId,
          }).url,
        ),
    });
  };

  const handlePageChange = (param) => (page) => {
    router.visit(
      showCurso({ instituicao: instituicaoId, cursoTutelado: cursoTuteladoId })
        .url,
      {
        data: {
          page_turmas: cursoTutelado.turmas?.current_page ?? 1,
          page_professores: cursoTutelado.professores?.current_page ?? 1,
          ano_lectivo_id: anoLectivoId,
          [param]: page,
        },
        preserveScroll: true,
        preserveState: true,
      },
    );
  };

  const handleAnoLectivoChange = (value) => {
    router.visit(
      showCurso({ instituicao: instituicaoId, cursoTutelado: cursoTuteladoId })
        .url,
      {
        data: { ano_lectivo_id: value },
        preserveScroll: true,
        preserveState: true,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      {/* Header */}
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex w-full items-end justify-between p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-bold md:text-3xl">
                {cursoTutelado.curso.nome}
              </h1>

              <p className="text-sm font-bold opacity-90">
                {cursoTutelado.instituicao.nome}
              </p>
            </div>

            {/* Menu três pontos */}
            {hasAnyAction && (
              <DropdownMenu>
                <DropdownMenuTrigger asChild>
                  <Button
                    variant="ghost"
                    size="icon"
                    className="text-white hover:bg-white/20"
                  >
                    <MoreHorizontalIcon />
                  </Button>
                </DropdownMenuTrigger>

                <DropdownMenuContent align="end">
                  {cursoTutelado.can?.update && (
                    <DropdownMenuItem
                      onClick={() =>
                        router.visit(
                          edit({
                            instituicao: instituicaoId,
                            cursoTutelado: cursoTuteladoId,
                          }).url,
                        )
                      }
                    >
                      Editar
                    </DropdownMenuItem>
                  )}

                  {/*<DropdownMenuItem variant="destructive">
                    Remover curso
                  </DropdownMenuItem>*/}
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
          <div>
            <p className="text-sm text-muted-foreground">Duração</p>

            <p className="text-sm font-bold">
              {cursoTutelado.curso.duracao_anos ? (
                `${cursoTutelado.curso.duracao_anos} anos`
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Classes</p>
            <div className="mt-1 flex flex-wrap gap-1">
              {cursoTutelado.classes?.length > 0 ? (
                <>
                  {cursoTutelado.classes.map((c) => (
                    <Tooltip key={c.id}>
                      <TooltipTrigger asChild>
                        <Link
                          href={
                            showClasse({
                              instituicao: instituicaoId,
                              cursoTutelado: cursoTuteladoId,
                              cursoClasse: c.id,
                            }).url
                          }
                          className="inline-block"
                        >
                          <Badge>{c.nome}</Badge>
                        </Link>
                      </TooltipTrigger>

                      <TooltipContent side="bottom">
                        {c.turnos?.length > 0 ? (
                          <ul className="flex gap-3 text-xs">
                            {c.turnos.map((turno, i) => (
                              <li key={i}>{turno}</li>
                            ))}
                          </ul>
                        ) : (
                          <p className="text-xs">Sem turnos definidos</p>
                        )}
                      </TooltipContent>
                    </Tooltip>
                  ))}
                </>
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Contadores */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
        <Card>
          <CardContent className="p-4">
            <p className="text-sm text-muted-foreground">Turmas</p>
            <h2 className="text-xl font-semibold">
              {cursoTutelado.contadores?.turmas ?? 0}
            </h2>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <p className="text-sm text-muted-foreground">Professores</p>
            <h2 className="text-xl font-semibold">
              {cursoTutelado.contadores?.professores ?? 0}
            </h2>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <p className="text-sm text-muted-foreground">Disciplinas</p>
            <h2 className="text-xl font-semibold">
              {cursoTutelado.contadores?.disciplinas ?? 0}
            </h2>
          </CardContent>
        </Card>
      </div>

      {/* Tabs */}
      <Tabs defaultValue="turmas">
        <TabsList variant={'line'} className="flex w-full justify-between">
          <div>
            <TabsTrigger value="turmas">Turmas</TabsTrigger>
            <TabsTrigger value="professores">Professores</TabsTrigger>
          </div>
          <div>
            <Select
              value={anoLectivoId ?? ''}
              onValueChange={handleAnoLectivoChange}
            >
              <SelectTrigger id="ano-lectivo" className="w-28">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Anos Lectivos</SelectLabel>
                  {anosLectivos?.map((ano) => (
                    <SelectItem key={ano.id} value={ano.id}>
                      {ano.nome}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </TabsList>

        <TabsContent value="turmas" className="mt-2">
          <TabTurmas
            instituicaoId={instituicaoId}
            cursoTuteladoId={cursoTuteladoId}
            turmas={cursoTutelado.turmas}
            can={cursoTutelado.can}
            anoLectivoId={anoLectivoId}
            pagination={{
              current_page: cursoTutelado.turmas?.current_page,
              last_page: cursoTutelado.turmas?.last_page,
            }}
            onPageChange={handlePageChange('page_turmas')}
          />
        </TabsContent>

        <TabsContent value="professores" className="mt-2">
          <TabProfessores
            instituicaoId={instituicaoId}
            cursoTuteladoId={cursoTuteladoId}
            professores={cursoTutelado.professores}
            can={cursoTutelado.can}
            deleteFn={handleDelete}
            pagination={{
              current_page: cursoTutelado.professores?.current_page,
              last_page: cursoTutelado.professores?.last_page,
            }}
            onPageChange={handlePageChange('page_professores')}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
