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
import { edit } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';

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
            <h1 className="text-2xl font-bold text-secondary md:text-3xl">
              {turma.nome}
            </h1>

            <p className="text-sm font-bold">
              {turma?.nome} — {turma.classe.nome}
            </p>
          </div>
        </div>
      </div>

      <CardContent className="grid grid-cols-1 gap-6 py-6 sm:grid-cols-2 md:grid-cols-4">
        <div>
          <p className="text-sm text-muted-foreground">Máximo de alunos</p>
          <p className="font-bold">
            {turma.max_alunos ?? (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Total de alunos</p>
          <p className="font-bold">
            {alunos?.total ?? turma.alunos?.length ?? 0}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Total de disciplinas</p>
          <p className="font-bold">
            {disciplinas?.total ??
              turma.curso_classe_turno?.classe_turno_disciplinas?.length ??
              0}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
