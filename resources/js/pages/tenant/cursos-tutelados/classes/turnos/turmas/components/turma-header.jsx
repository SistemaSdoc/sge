import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowUpLeft, ArrowUpRight, MoreHorizontalIcon } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { show as showClasse } from '@/actions/App/Http/Controllers/Tenant/CursoClasseController';
import { show as showCurso } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { edit as editTurma } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';
import { index } from '@/actions/App/Http/Controllers/Tenant/ConfirmacaoMatriculaController';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';

export function Header({
  can,
  turma,
  disciplinas,
  alunos,
  anosLectivos,
  anoLectivoId,
  params,
  deleteFn,
}) {
  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          {/* Breadcrumb e Descrição */}
          <div className="min-w-0 space-y-1">
            <CardTitle className="flex items-center gap-1">
              <Breadcrumb>
                <BreadcrumbList>
                  <BreadcrumbItem>
                    <BreadcrumbLink asChild>
                      {can.curso.view ? (
                        <Link
                          className="line-clamp-1 text-sm font-semibold text-primary hover:text-primary/80"
                          href={
                            showCurso({
                              instituicao: params.instituicao.id,
                              cursoTutelado: params.cursoTutelado.id,
                            }).url
                          }
                        >
                          {params.cursoTutelado?.nome}
                        </Link>
                      ) : (
                        <span className="line-clamp-1 text-sm font-semibold text-primary">
                          {params.cursoTutelado?.nome}
                        </span>
                      )}
                    </BreadcrumbLink>
                  </BreadcrumbItem>

                  <BreadcrumbSeparator />

                  <BreadcrumbItem>
                    <BreadcrumbLink asChild>
                      {can.classe.view ? (
                        <Link
                          className="line-clamp-1 text-sm font-semibold text-primary hover:text-primary/80"
                          href={
                            showClasse({
                              instituicao: params.instituicao.id,
                              cursoTutelado: params.cursoTutelado.id,
                              cursoClasse: params.cursoClasse.id,
                            }).url
                          }
                        >
                          {params.cursoClasse?.nome}
                        </Link>
                      ) : (
                        <span className="line-clamp-1 text-sm font-semibold text-primary">
                          {params.cursoClasse?.nome}
                        </span>
                      )}
                    </BreadcrumbLink>
                  </BreadcrumbItem>

                  <BreadcrumbSeparator />

                  <BreadcrumbItem>
                    <BreadcrumbLink asChild>
                      {can.turno.view ? (
                        <Link
                          className="line-clamp-1 text-sm font-semibold text-primary hover:text-primary/80"
                          href={
                            showClasse({
                              instituicao: params.instituicao.id,
                              cursoTutelado: params.cursoTutelado.id,
                              cursoClasse: params.cursoClasse.id,
                            }).url
                          }
                          data={{ turno: params.cursoClasseTurno.id }}
                        >
                          {params.cursoClasseTurno?.nome}
                        </Link>
                      ) : (
                        <span className="line-clamp-1 text-sm font-semibold text-primary">
                          {params.cursoClasseTurno?.nome}
                        </span>
                      )}
                    </BreadcrumbLink>
                  </BreadcrumbItem>

                  <BreadcrumbSeparator />

                  <BreadcrumbItem>
                    <BreadcrumbPage className="line-clamp-1 text-sm font-semibold text-secondary">
                      {turma.nome}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
            </CardTitle>

            <CardDescription>
              Gerir alunos e disciplinas desta turma no ano lectivo{' '}
              <span className="font-bold">
                {anosLectivos.find((ano) => ano.id === anoLectivoId)?.nome}
              </span>
            </CardDescription>
          </div>

          {/* Botões - Responsivos */}
          {(can.curso?.view ||
            turma.can?.edit ||
            can.confirmarMatricula.viewAny) && (
            <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
              {/* Botão de voltar */}
              {can.classe?.view && (
                <Button
                  variant="outline"
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={() =>
                    router.visit(
                      showClasse({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                        cursoClasse: params.cursoClasse.id,
                      }).url,
                    )
                  }
                >
                  <ArrowUpLeft className="shrink-0" /> Voltar a classe
                </Button>
              )}

              {/* Botão de edição de turma */}
              {turma.can?.edit && (
                <Button
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={(e) => {
                    e.stopPropagation();
                    router.visit(
                      editTurma({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                        cursoClasse: params.cursoClasse.id,
                        cursoClasseTurno: params.cursoClasseTurno.id,
                        turma: params.turma,
                      }).url + '?origem=classe',
                    );
                  }}
                >
                  Editar Turma
                </Button>
              )}

              {/* Botão Confirmar Matrículas - Normal em mobile, hidden em sm+ */}
              {can.confirmarMatricula.viewAny && (
                <Button
                  size="sm"
                  variant="outline"
                  className="w-full justify-center sm:hidden"
                  onClick={() =>
                    router.visit(
                      index({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                        cursoClasse: params.cursoClasse.id,
                        cursoClasseTurno: params.cursoClasseTurno.id,
                        turma: params.turma,
                      }).url,
                    )
                  }
                >
                  Confirmar Matrículas
                  <ArrowUpRight className="shrink-0" />
                </Button>
              )}

              {/* Dropdown Menu - Hidden em mobile, visible em sm+ */}
              {can.confirmarMatricula.viewAny && (
                <div className="hidden sm:block">
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="outline" size="icon-sm">
                        <MoreHorizontalIcon />
                      </Button>
                    </DropdownMenuTrigger>

                    <DropdownMenuContent align="end" className="w-auto">
                      <DropdownMenuItem
                        onClick={() =>
                          router.visit(
                            index({
                              instituicao: params.instituicao.id,
                              cursoTutelado: params.cursoTutelado.id,
                              cursoClasse: params.cursoClasse.id,
                              cursoClasseTurno: params.cursoClasseTurno.id,
                              turma: params.turma,
                            }).url,
                          )
                        }
                      >
                        Confirmar Matrículas
                        <ArrowUpRight />
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              )}
            </div>
          )}
        </div>
      </CardHeader>

      {/* Cards de métricas */}
      <div className="grid grid-cols-2 divide-x bg-muted/50 text-center">
        <div className="px-4 py-4">
          <p className="text-sm font-bold">
            {alunos?.total ?? turma.alunos?.length ?? 0}
          </p>

          <p className="text-xs text-muted-foreground">Alunos nesta turma</p>
        </div>

        <div className="px-4 py-4">
          <p className="text-sm font-bold">
            {disciplinas?.total ??
              turma.curso_classe_turno?.classe_turno_disciplinas?.length ??
              0}
          </p>

          <p className="text-xs text-muted-foreground">
            Disciplinas lecionadas nesta turma
          </p>
        </div>
      </div>
    </Card>
  );
}
