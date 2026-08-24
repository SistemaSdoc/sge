import { router } from '@inertiajs/react';
import { Card } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { MoreHorizontalIcon, Minus, Pencil, Dot, Download } from 'lucide-react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

function getInitials(nome = '') {
  return nome
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((n) => n[0])
    .join('')
    .toUpperCase();
}

export function AlunoHeader({ aluno }) {
  const hasAnyAction =
    aluno.can?.view || aluno.can?.update || aluno.can?.delete;

  return (
    <Card className="overflow-hidden pt-0!">
      {/* Banner */}
      <div className="relative h-40 w-full bg-muted md:h-56">
        {aluno.banner_url && (
          <img
            src={aluno.banner_url}
            alt=""
            className="absolute inset-0 h-full w-full object-cover"
          />
        )}

        <div className="absolute inset-0 bg-black/20" />

        {hasAnyAction && (
          <div className="absolute top-4 right-4 z-10 flex items-center gap-2">
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
                    <Pencil className="mr-2 size-4" />
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
          </div>
        )}
      </div>

      {/* Avatar sobreposto + info */}
      <div className="relative flex flex-col items-center px-6 pb-0 text-center">
        <Avatar className="-mt-14 size-28 border-4 border-background shadow-sm md:-mt-16 md:size-32">
          <AvatarImage src={aluno.foto_url} alt={aluno.nome} />
          <AvatarFallback className="text-xl">
            {getInitials(aluno.nome)}
          </AvatarFallback>
        </Avatar>

        <h1 className="mt-3 text-2xl font-semibold md:text-3xl">
          {aluno.nome}
        </h1>


        <div className="mt-2 flex flex-wrap items-center justify-center gap-x-1 gap-y-1 text-sm text-muted-foreground">
          <span className="flex items-center gap-1">
            <span className="">Curso:</span>{' '}
            {aluno.curso || <Minus size={14} />} <Dot />
          </span>

          <span className="flex items-center gap-1">
            Classe: {aluno.turma.classe || <Minus size={14} />} <Dot />
          </span>

          <span className="flex items-center gap-1">
            Turno: {aluno.turno || <Minus size={14} />} <Dot />
          </span>

          <span className="flex items-center gap-1">
            Turma: {aluno.turma.nome || <Minus size={14} />} <Dot />
          </span>

          {/* Número de processo */}
          <span className="flex items-center gap-1">
            Nº Proc.: {aluno.numero_processo || <Minus size={14} />}
          </span>
        </div>
      </div>
    </Card>
  );
}
