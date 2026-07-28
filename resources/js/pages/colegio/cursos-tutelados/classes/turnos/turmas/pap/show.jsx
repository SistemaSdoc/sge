import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
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
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy as destroyJurado } from '@/actions/App/Http/Controllers/BancaJuriPapController';
import { destroy as destroyIntegrante } from '@/actions/App/Http/Controllers/ElementoGrupoPapController';
import { edit } from '@/actions/App/Http/Controllers/GrupoPapController';
import { definirData } from '@/actions/App/Http/Controllers/GrupoPapController';
import { actualizarNota } from '@/actions/App/Http/Controllers/ElementoGrupoPapController';
import { FieldError } from '@/components/ui/field';
import { usePagination } from '@/hooks/use-pagination';


export default function Show({
  instituicao,
  colegio,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  grupoPap,
  banca,
  elementos,
  can,
}) {
  const [notas, setNotas] = useState({});
  const [dialogDataAberto, setDialogDataAberto] = useState(false);
  const bancaPagination = usePagination('banca');
  const elementosPagination = usePagination('elementos');
  const hasAnyAction = Boolean(can?.update || can?.definirData);

  const params = {
    instituicao: instituicao.id,
    colegio: colegio.id,
    cursoTutelado: cursoTutelado.id,
    cursoClasse: cursoClasse.id,
    cursoClasseTurno: cursoClasseTurno.id,
    turma: turma.id,
    grupoPap: grupoPap.id,
  };

  const { data, setData, put, processing, errors, reset, setError } = useForm({
    data_defesa: grupoPap?.data_defesa
      ? grupoPap.data_defesa.split('T')[0]
      : '',
    local_defesa: grupoPap?.local_defesa ?? '',
  });


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

  const submeterDataDefesa = () => {
    if (!data.data_defesa) {
      setError('data_defesa', 'A data da defesa é obrigatória.');
      return;
    }

    put(definirData.url(params), {
      onSuccess: () => {
        setDialogDataAberto(false);
        router.reload();
      },
    });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
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

                <DropdownMenuContent align="end" className="w-full max-w-2xl">
                  {can?.update && (
                    <DropdownMenuItem
                      onClick={() => router.visit(edit.url(params))}
                    >
                      Editar
                    </DropdownMenuItem>
                  )}

                  {can?.definirData && (
                    <DropdownMenuItem onClick={() => setDialogDataAberto(true)}>
                      Definir data da defesa
                    </DropdownMenuItem>
                  )}
                </DropdownMenuContent>
              </DropdownMenu>
            )}
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
          <div>
            <p className="text-sm text-muted-foreground">Professor tutor</p>
            <p className="font-medium">
              {grupoPap?.professor ? (
                <Link
                  href={`/dashboard/professores/${grupoPap.professor.id}`}
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
            <p className="text-sm text-muted-foreground">Local & Data de defesa</p>
            <p className="font-medium">
              {grupoPap?.local_defesa}/{grupoPap?.data_defesa
                ? new Date(grupoPap.data_defesa).toLocaleDateString('pt-PT', {
                  weekday: 'short',
                  day: '2-digit',
                  month: 'short',
                  year: 'numeric',
                })
                : 'Por definir...'}
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Dialog — Definir data da defesa */}
      <Dialog
        open={dialogDataAberto}
        onOpenChange={(aberto) => {
          setDialogDataAberto(aberto);
          if (!aberto) reset();
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>Definir data da defesa</DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-2">
            <div className="space-y-1.5">
              <Label htmlFor="data_defesa">Data da defesa</Label>
              <Input
                id="data_defesa"
                type="date"
                value={data.data_defesa}
                onChange={(e) => setData('data_defesa', e.target.value)}
              />
              {errors.data_defesa && (
                <FieldError>{errors.data_defesa}</FieldError>
              )}
            </div>

            <div className="space-y-1.5">
              <Label htmlFor="local_defesa">Local da defesa</Label>
              <Input
                id="local_defesa"
                type="text"
                placeholder="Ex: Sala 12, Bloco A"
                value={data.local_defesa}
                onChange={(e) => setData('local_defesa', e.target.value)}
              />
              {errors.local_defesa && (
                <FieldError>{errors.local_defesa}</FieldError>
              )}
            </div>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => {
                setDialogDataAberto(false);
                reset();
              }}
            >
              Cancelar
            </Button>
            <Button onClick={submeterDataDefesa} disabled={processing}>
              {processing ? 'A definir...' : 'Definir data'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

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
            pagination={elementos}
            onPageChange={elementosPagination.handlePageChange}
            actualizarNotaFn={actualizarNotaFn}
            can={can}
          />
        </TabsContent>

        <TabsContent value="integrantes-banca">
          <TabBanca
            params={params}
            grupoPap={grupoPap}
            removerJuradoFn={removerJuradoFn}
            pagination={banca}
            onPageChange={bancaPagination.handlePageChange}
            can={can}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
