import { router } from '@inertiajs/react';
import { Minus, MoreHorizontalIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { edit } from '@/actions/App/Http/Controllers/InstituicaoController';
import { edit as editarPrazosLancamentoNotas } from '@/actions/App/Http/Controllers/PeriodoLancamentoNotasController';

export function InstituicaoCabecalho({ data, storageUrl, can = {} }) {
  const canEdit = Boolean(can?.edit || can?.edit_instituicao);
  const canGerirPrazos = Boolean(can?.gerir_prazos);

  return (
    <Card className="overflow-hidden pt-0!">
      <div className="relative flex h-56 w-full items-end overflow-hidden bg-muted">
        {data.logo ? (
          <img
            src={`${storageUrl}/${data.logo}`}
            alt={`Logo ${data.nome}`}
            sizes="(max-width: 768px) 100vw, 1000px"
            loading="lazy"
            className="absolute inset-0 h-full w-full object-cover object-center"
          />
        ) : (
          <div className="absolute inset-0 h-full w-full bg-linear-to-br from-muted/60 to-muted/40" />
        )}

        {/* overlay: z-10 para ficar acima da imagem */}
        <div className="absolute inset-0 z-10 bg-black/50" />

        {/* conteúdo: z-20 para ficar acima do overlay */}
        <div className="relative z-20 flex w-full items-end justify-between p-6">
          <div className="space-y-2 text-white">
            <h1 className="text-2xl font-semibold md:text-3xl">{data.nome}</h1>

            <p className="text-sm opacity-90">
              {data.sigla} - {data.email}
            </p>
          </div>

          {(canEdit || canGerirPrazos) && (
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
                {canEdit && (
                  <DropdownMenuItem
                    onClick={() =>
                      router.visit(edit({ instituicao: data.id }).url)
                    }
                    disabled={!canEdit}
                  >
                    Editar
                  </DropdownMenuItem>
                )}

                {canEdit && canGerirPrazos && <DropdownMenuSeparator />}

                {canGerirPrazos && (
                  <DropdownMenuItem
                    onClick={() =>
                      router.visit(editarPrazosLancamentoNotas(data.id).url)
                    }
                  >
                    Prazos de lançamento
                  </DropdownMenuItem>
                )}
              </DropdownMenuContent>
            </DropdownMenu>
          )}
        </div>
      </div>

      <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
        <div>
          <p className="text-sm text-muted-foreground">Telefone</p>
          <p className="font-medium">
            {data.telefone || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Endereço</p>
          <p className="font-medium">
            {data.endereco || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
