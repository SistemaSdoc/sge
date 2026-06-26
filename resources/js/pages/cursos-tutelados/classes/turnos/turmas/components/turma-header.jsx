import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Minus, MoreHorizontalIcon } from 'lucide-react';
import { router } from '@inertiajs/react';
import { edit } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export function Header({
  turma,
  disciplinas,
  alunos,
  preview,
  routeParams,
  params,
  deleteFn,
}) {
  return (
    <Card className="overflow-hidden pt-0! pb-0">
      <div className="relative flex h-56 w-full items-end bg-muted">
        <div className="absolute inset-0 bg-black/50" />
        <div className="relative z-10 flex w-full items-end justify-between p-6">
          <div className="space-y-2 text-white">
            <h1 className="text-2xl font-semibold md:text-3xl">{turma.nome}</h1>

            <p className="text-sm opacity-90">
              {turma?.nome} — {turma.classe.nome}
            </p>
          </div>

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
              <DropdownMenuItem
                onClick={(e) => {
                  e.stopPropagation();
                  router.visit(edit(params).url + '?origem=classe');
                }}
              >
                Editar
              </DropdownMenuItem>

              <DropdownMenuSeparator />

              <DropdownMenuItem
                onClick={() => router.visit(preview({ ...routeParams }).url)}
              >
                Progressão de Alunos
              </DropdownMenuItem>

              {/* ── NOVO ── 
              {totalRecurso > 0 && (
                <DropdownMenuItem
                  className="text-blue-600"
                  onClick={() =>
                    router.visit(
                      `/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/recurso`,
                    )
                  }
                >
                  Lançar Recurso
                  <Badge className="ml-auto bg-blue-50 text-xs text-blue-600">
                    {totalRecurso} alunos
                  </Badge>
                </DropdownMenuItem>
              )}*/}

              <DropdownMenuSeparator />

              <DropdownMenuItem
                variant="destructive"
                onClick={(e) => {
                  e.stopPropagation();
                  deleteFn(params);
                }}
              >
                Remover
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>

      <CardContent className="grid grid-cols-1 gap-6 py-6 sm:grid-cols-2 md:grid-cols-4">
        <div>
          <p className="text-sm text-muted-foreground">Máximo de alunos</p>
          <p className="font-medium">
            {turma.max_alunos ?? (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Total de alunos</p>
          <p className="font-medium">
            {alunos?.total ?? turma.alunos?.length ?? 0}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Total de disciplinas</p>
          <p className="font-medium">
            {disciplinas?.total ??
              turma.curso_classe_turno?.classe_turno_disciplinas?.length ??
              0}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
