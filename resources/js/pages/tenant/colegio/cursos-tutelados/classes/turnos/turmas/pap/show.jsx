import { useState } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { TabBanca } from './components/tabs/tab-banca';
//import { TabAprovacao } from './components/tabs/tab-aprovacao';
import { TabHistorico } from './components/tabs/tab-historico';
import { Card, CardContent } from '@/components/ui/card';
import { Minus, MoreHorizontalIcon, FileText } from 'lucide-react';
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
import { destroy as destroyJurado } from '@/actions/App/Http/Controllers/Tenant/BancaJuriPapController';
import { destroy as destroyIntegrante } from '@/actions/App/Http/Controllers/Tenant/ElementoGrupoPapController';
import { edit } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { definirData } from '@/actions/App/Http/Controllers/Tenant/Colegios/GrupoPapController';
import { actualizarNota } from '@/actions/App/Http/Controllers/Tenant/Colegios/ElementoGrupoPapController';
import { FieldError } from '@/components/ui/field';
import { usePagination } from '@/hooks/use-pagination';
// Adicionar no topo dos imports
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { AlertCircle } from 'lucide-react';
import { editar as editarTema } from '@/actions/App/Http/Controllers/Tenant/GrupoPapAprovacaoController'; // ajustar o import real
import { TabAprovacao } from './components/tabs/tab-aprovacao';

export default function Show({
  instituicao,
  colegio,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  grupoPap,
  historico,
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
    instituicao: instituicao.id, // ← para os controllers da instituição (ElementoGrupoPap, etc.)
    colegio: colegio.id, // ← para os controllers do colégio (BancaJuriPap, etc.)
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
    if (!data.hora_defesa) {
      setError('hora_defesa', 'A hora da defesa é obrigatória.');
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
              <h2 className="md:text-1xl text-2xl font-semibold">
                Tema: {grupoPap?.tema_grupo}
              </h2>
              <p className="text-sm opacity-90">{colegio?.nome}</p>
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
            <p className="text-sm text-muted-foreground">
              Local & Data de defesa
            </p>
            <p className="font-medium">
              {grupoPap?.local_defesa ? `${grupoPap.local_defesa} / ` : ''}
              {grupoPap?.data_defesa
                ? new Date(grupoPap.data_defesa).toLocaleString('pt-PT', {
                    weekday: 'short',
                    day: '2-digit',
                    month: 'short',
                    year: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                  })
                : 'Por definir...'}
            </p>
          </div>

          {grupoPap?.criterios_pap_url && (
            <div>
              <p className="text-sm text-muted-foreground">Critérios PAP</p>

              <a
                href={grupoPap.criterios_pap_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
              >
                <FileText className="size-4" />
                Ver documento
              </a>
            </div>
          )}

          {grupoPap?.manual_pt_url && (
            <div>
              <p className="text-sm text-muted-foreground">Manual de PT</p>

              <a
                href={grupoPap.manual_pt_url}
                target="_blank"
                rel="noopener noreferrer"
                className="flex items-center gap-1.5 text-sm font-medium text-primary hover:underline"
              >
                <FileText className="size-4" />
                Ver documento
              </a>
            </div>
          )}
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
              <Label htmlFor="hora_defesa">Hora da defesa</Label>
              <Input
                id="hora_defesa"
                type="time"
                value={data.hora_defesa}
                onChange={(e) => setData('hora_defesa', e.target.value)}
              />
              {errors.hora_defesa && (
                <FieldError>{errors.hora_defesa}</FieldError>
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
      {/* Banner de ação — reprovado ou melhoria solicitada */}
      {['reprovado', 'melhoria-solicitada'].includes(
        grupoPap.status_aprovacao,
      ) && (
        <Alert
          variant={
            grupoPap.status_aprovacao === 'reprovado'
              ? 'destructive'
              : 'default'
          }
        >
          <AlertCircle className="h-4 w-4" />
          <AlertTitle>
            {grupoPap.status_aprovacao === 'reprovado'
              ? 'Tema reprovado'
              : 'Melhoria solicitada'}
          </AlertTitle>
          <AlertDescription className="mt-2 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <span className="text-sm">
              {grupoPap.comentario_aprovacao ?? 'Sem comentário adicional.'}
            </span>
            {can?.corrigirTema && (
              <Button
                size="sm"
                variant={
                  grupoPap.status_aprovacao === 'reprovado'
                    ? 'destructive'
                    : 'default'
                }
                onClick={() =>
                  router.visit(editarTema.url({ grupoPap: grupoPap.id }))
                }
              >
                {grupoPap.status_aprovacao === 'reprovado'
                  ? 'Enviar Novo Tema'
                  : 'Corrigir Tema'}
              </Button>
            )}
          </AlertDescription>
        </Alert>
      )}

      <Tabs defaultValue="integrantes-grupo" className="w-full">
        <TabsList>
          <TabsTrigger value="integrantes-grupo">
            Integrantes do grupo
          </TabsTrigger>
          {can?.verBanca && (
            <TabsTrigger value="integrantes-banca">
              Integrantes da banca
            </TabsTrigger>
          )}
          <TabsTrigger value="aprovacao">Aprovação do tema</TabsTrigger>

          <TabsTrigger value="historico">Histórico</TabsTrigger>
        </TabsList>

        <TabsContent value="integrantes-grupo">
          <TabIntegrantes
            grupoPap={grupoPap}
            params={params}
            setNotas={setNotas}
            actualizarNotaFn={actualizarNotaFn}
            notas={notas}
            pagination={elementos}
            onPageChange={elementosPagination.handlePageChange}
            can={can}
          />
        </TabsContent>
        {can?.verBanca && (
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
        )}
        <TabsContent value="aprovacao">
          <TabAprovacao params={params} grupoPap={grupoPap} can={can} />
        </TabsContent>

        <TabsContent value="historico">
          <TabHistorico
            params={params}
            grupoPap={grupoPap}
            instituicao={instituicao}
            cursoTutelado={cursoTutelado}
            historico={historico}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
