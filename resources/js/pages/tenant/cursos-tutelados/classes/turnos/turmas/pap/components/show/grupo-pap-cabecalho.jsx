import { MoreHorizontalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';

export function GrupoPapCabecalho({
  grupoPap,
  can,
  onEdit,
  onDefinirTema,
  onDefinirData,
  onDelete,
}) {
  return (
    <div className="relative flex h-56 w-full items-end bg-muted">
      <div className="absolute inset-0 bg-black/50" />
      <div className="relative z-10 flex w-full items-end justify-between p-6">
        <div className="space-y-2 text-white">
          <h1 className="text-2xl font-semibold md:text-3xl">
            {grupoPap?.nome_grupo}
          </h1>

          <h2 className="md:text-1xl text-2xl font-semibold">
            Tema: {grupoPap?.tema_grupo}
          </h2>
          <p className="max-w-3xl text-sm text-white/90">
            Estudo de caso: {grupoPap?.estudo_caso || 'Não definido'}
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

          <DropdownMenuContent align="end" className="w-full max-w-2xl">
            {can?.update && (
              <DropdownMenuItem onClick={onEdit}>Editar</DropdownMenuItem>
            )}

            {can?.definirTema && (
              <DropdownMenuItem onClick={onDefinirTema}>
                {grupoPap.status_aprovacao === 'rascunho'
                  ? 'Definir Tema'
                  : 'Corrigir Tema'}
              </DropdownMenuItem>
            )}

            {can?.definirData && (
              <DropdownMenuItem onClick={onDefinirData}>
                Definir data da defesa
              </DropdownMenuItem>
            )}

            {can?.delete && (
              <DropdownMenuItem variant="destructive" onClick={onDelete}>
                Remover grupo PAP
              </DropdownMenuItem>
            )}
          </DropdownMenuContent>
        </DropdownMenu>
      </div>
    </div>
  );
}
