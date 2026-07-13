import { router } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { MoreHorizontalIcon, Minus } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function AlunoHeader({ aluno }) {
  const hasAnyAction = aluno.can?.update || aluno.can?.delete;

  return (
    <Card className="overflow-hidden pt-0!">
      <div className="relative flex h-56 w-full items-end bg-muted">
        <div className="absolute inset-0 bg-black/50" />
        <div className="relative z-10 flex w-full items-end justify-between p-6">
          <div className="space-y-2 text-white">
            <h1 className="text-2xl font-semibold md:text-3xl">{aluno.nome}</h1>
            <p className="text-sm opacity-90">
              {aluno.matricula ?? <Minus size={15} className="opacity-60" />}
            </p>
          </div>

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
                {aluno.can?.update && (
                  <DropdownMenuItem
                    onClick={() =>
                      router.visit(`/dashboard/alunos/${aluno.id}/edit`)
                    }
                  >
                    Editar
                  </DropdownMenuItem>
                )}

                {aluno.can?.update && aluno.can?.delete && (
                  <DropdownMenuSeparator />
                )}

                {aluno.can?.delete && (
                  <DropdownMenuItem
                    variant="destructive"
                    onClick={() =>
                      router.delete(`/dashboard/alunos/${aluno.id}`, {
                        onSuccess: () => router.visit('/dashboard/alunos'),
                      })
                    }
                  >
                    Remover
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      </div>
    </Card>
  );
}
