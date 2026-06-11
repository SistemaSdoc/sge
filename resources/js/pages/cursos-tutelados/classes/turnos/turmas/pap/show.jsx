import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { TabBanca } from './components/tabs/tab-banca';
import { Card, CardContent } from '@/components/ui/card';
import { Minus, MoreHorizontalIcon } from 'lucide-react';
import { TabIntegrantes } from './components/tabs/tab-integrantes';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { destroy as destroyJurado } from '@/actions/App/Http/Controllers/BancaJuriPapController';
import { destroy as destroyIntegrante } from '@/actions/App/Http/Controllers/ElementoGrupoPapController';
import { edit } from '@/actions/App/Http/Controllers/GrupoPapController';
import { actualizarNota } from '@/actions/App/Http/Controllers/ElementoGrupoPapController';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  grupoPap,
}) {
  const [notas, setNotas] = useState({});

  const params = {
    instituicao: instituicao.id,
    cursoTutelado: cursoTutelado.id,
    cursoClasse: cursoClasse.id,
    cursoClasseTurno: cursoClasseTurno.id,
    turma: turma.id,
    grupoPap: grupoPap.id,
  };

  const removerIntegranteFn = (elementoGrupoPap) => {
    router.delete(destroyIntegrante.url({ ...params, elementoGrupoPap }), {
      onSuccess: () => router.reload(),
    });
  };

  const removerJuradoFn = (bancaJuriPap) => {
    router.delete(destroyJurado.url({ ...params, bancaJuriPap }), {
      onSuccess: () => router.reload(),
    });
  };

  const actualizarNotaFn = (payload, options = {}) => {
    router.put(
      actualizarNota.url({ ...params, elementoGrupoPap: payload.elementoId }),
      payload.data,
      {
        onSuccess: () => {
          options.onSuccess?.();
          router.reload();
        },
        onError: options.onError,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6">
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex w-full items-end justify-between p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-semibold md:text-3xl">
                {grupoPap?.nome_grupo}
              </h1>
              <p className="text-sm opacity-90">{grupoPap?.tema_grupo}</p>
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

              <DropdownMenuContent align="end" className="w-full max-w-4">
                <DropdownMenuItem
                  onClick={() => router.visit(edit.url(params))}
                >
                  Editar
                </DropdownMenuItem>

                {/*
                  <DropdownMenuItem
                    variant="destructive"
                    onClick={removerJuradoFn}
                  >
                    Remover grupo
                  </DropdownMenuItem>
                */}
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
          <div>
            <p className="text-sm text-muted-foreground">Professor tutor</p>
            <p className="font-medium">
              {grupoPap?.professor ? (
                <Link
                  href={`/professores/${grupoPap.professor.id}`}
                  className="hover:underline"
                >
                  {grupoPap.professor.nome}
                </Link>
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Turma</p>
            <p className="font-medium">
              {turma?.nome ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Status</p>
            <p className="font-medium">
              {grupoPap?.status ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Data de defesa</p>
            <p className="font-medium">
              {grupoPap?.data_defesa ?? 'Por definir...'}
            </p>
          </div>
        </CardContent>
      </Card>

      <Tabs defaultValue="integrantes-grupo" className="w-full">
        <TabsList>
          <TabsTrigger value="integrantes-grupo">
            Integrantes do grupo
          </TabsTrigger>

          <TabsTrigger value="integrantes-banca">
            Integrantes da banca
          </TabsTrigger>
        </TabsList>

        <TabsContent value="integrantes-grupo">
          <TabIntegrantes
            grupoPap={grupoPap}
            params={params}
            setNotas={setNotas}
            notas={notas}
            actualizarNotaFn={actualizarNotaFn}
            removerIntegranteFn={removerIntegranteFn}
          />
        </TabsContent>

        <TabsContent value="integrantes-banca">
          <TabBanca
            params={params}
            grupoPap={grupoPap}
            removerJuradoFn={removerJuradoFn}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
