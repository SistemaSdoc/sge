import { useState } from 'react';
import { router, useForm } from '@inertiajs/react';
import { BookOpenText } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { EmptyState } from '@/components/empty-state';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { useDialog } from '@/hooks/use-dialog';
import TablePagination from '@/components/table-pagination';
import {
  destroySugestaoTema,
  storeSugestaoTema,
  updateSugestaoTema,
  aprovarSugestaoTema,
  rejeitarSugestaoTema,
  solicitarMelhoriaSugestaoTema,
  resubmeterSugestaoTema,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

const ESTADO_CONFIG = {
  pendente: { label: 'Pendente', variant: 'secondary' },
  aprovada: { label: 'Aprovada', variant: 'default' },
  rejeitada: { label: 'Rejeitada', variant: 'destructive' },
  melhoria_solicitada: { label: 'Melhoria solicitada', variant: 'warning' },
};

const MODAL_CONFIG = {
  rejeitar: {
    titulo: 'Rejeitar sugestão',
    descricao: 'Indique o motivo da rejeição. Esta informação será visível para quem gere as sugestões.',
    label: 'Motivo da rejeição',
    confirmar: 'Rejeitar',
  },
  melhoria: {
    titulo: 'Solicitar melhoria',
    descricao: 'Indique o que deve ser melhorado nesta sugestão de tema.',
    label: 'O que deve ser melhorado',
    confirmar: 'Solicitar melhoria',
  },
};

export function TabSugestoesTemas({
  params,
  sugestoes = [],
  pagination = {},
  onPageChange,
  canManage = false,
  canAprovar = false,
  tutelaExterna = false,
}) {
  const [formularioAberto, setFormularioAberto] = useState(false);
  const [sugestaoEmEdicao, setSugestaoEmEdicao] = useState(null);
  const [modalObservacao, setModalObservacao] = useState(null); // { tipo: 'rejeitar'|'melhoria', sugestao }
  const [observacao, setObservacao] = useState('');
  const [submitted, setSubmitted] = useState(false);
  const [processando, setProcessando] = useState(false);

  const { deleteConfirm } = useDialog();
  const { data, setData, post, put, processing, errors, reset, clearErrors } =
    useForm({ titulo: '', descricao: '' });

  const paramsSugestao = (sugestao) => ({
    instituicao: params.instituicao.id,
    cursoTutelado: params.cursoTutelado.id,
    sugestao: sugestao.id,
  });

  // ── Formulário de criação/edição ─────────────────────────────────────────

  const fecharFormulario = () => {
    setFormularioAberto(false);
    setSugestaoEmEdicao(null);
    reset();
    clearErrors();
  };

  const abrirFormularioCriacao = () => {
    setSugestaoEmEdicao(null);
    reset();
    clearErrors();
    setFormularioAberto(true);
  };

  const abrirFormularioEdicao = (sugestao) => {
    setSugestaoEmEdicao(sugestao);
    setData({ titulo: sugestao.titulo, descricao: sugestao.descricao ?? '' });
    clearErrors();
    setFormularioAberto(true);
  };

  const submeterSugestao = (event) => {
    event.preventDefault();
    const base = {
      instituicao: params.instituicao.id,
      cursoTutelado: params.cursoTutelado.id,
    };
    const options = { preserveScroll: true, onSuccess: fecharFormulario };

    if (sugestaoEmEdicao) {
      put(updateSugestaoTema({ ...base, sugestao: sugestaoEmEdicao.id }).url, options);
      return;
    }

    post(storeSugestaoTema(base).url, options);
  };

  const removerSugestao = (sugestao) => {
    deleteConfirm({
      title: 'Remover sugestão?',
      description: `A sugestão "${sugestao.titulo}" deixará de estar disponível para este curso.`,
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(destroySugestaoTema(paramsSugestao(sugestao)).url, {
          preserveScroll: true,
        }),
    });
  };

  // ── Acções de aprovação ──────────────────────────────────────────────────

  const aprovar = (sugestao) => {
    router.post(
      aprovarSugestaoTema(paramsSugestao(sugestao)).url,
      {},
      { preserveScroll: true },
    );
  };

  const resubmeter = (sugestao) => {
    router.post(
      resubmeterSugestaoTema(paramsSugestao(sugestao)).url,
      {},
      { preserveScroll: true },
    );
  };

  const abrirModalObservacao = (tipo, sugestao) => {
    setModalObservacao({ tipo, sugestao });
    setObservacao('');
    setSubmitted(false);
  };

  const fecharModalObservacao = () => {
    setModalObservacao(null);
    setObservacao('');
    setSubmitted(false);
  };

  const submeterObservacao = () => {
    setSubmitted(true);
    if (!observacao.trim()) return;

    const url = modalObservacao.tipo === 'rejeitar'
      ? rejeitarSugestaoTema(paramsSugestao(modalObservacao.sugestao)).url
      : solicitarMelhoriaSugestaoTema(paramsSugestao(modalObservacao.sugestao)).url;

    setProcessando(true);
    router.post(
      url,
      { observacao },
      {
        preserveScroll: true,
        onSuccess: fecharModalObservacao,
        onFinish: () => setProcessando(false),
      },
    );
  };

  // ── Render ────────────────────────────────────────────────────────────────

  const mostrarColunaAccoes = canManage || canAprovar;

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>
            Sugestões de temas ({pagination.total ?? sugestoes.length})
          </CardTitle>
          <CardDescription>
            {tutelaExterna
              ? 'Temas de referência definidos pela instituição tutora para orientar a escolha dos temas da PAP neste curso.'
              : 'Registe temas de referência para orientar a escolha dos temas da PAP neste curso.'}
          </CardDescription>
          {canManage && (
            <CardAction>
              <Button onClick={abrirFormularioCriacao}>
                Adicionar sugestão
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          {sugestoes.length === 0 ? (
            <EmptyState
              variant="table"
              icon={BookOpenText}
              title="Nenhuma sugestão cadastrada"
              description={
                tutelaExterna
                  ? 'A instituição tutora ainda não registou sugestões de temas.'
                  : 'Adicione temas de referência para este curso.'
              }
              action={
                canManage && {
                  label: 'Adicionar sugestão',
                  onClick: abrirFormularioCriacao,
                  variant: 'outline',
                }
              }
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Tema sugerido</TableHead>
                  <TableHead>Descrição</TableHead>
                  <TableHead>Estado</TableHead>
                  {mostrarColunaAccoes && (
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  )}
                </TableRow>
              </TableHeader>
              <TableBody>
                {sugestoes.map((sugestao) => {
                  const estado = ESTADO_CONFIG[sugestao.estado_aprovacao] ?? ESTADO_CONFIG.pendente;

                  return (
                    <TableRow key={sugestao.id}>
                      <TableCell className="px-4 font-medium">
                        {sugestao.titulo}
                      </TableCell>
                      <TableCell className="max-w-xl whitespace-normal text-muted-foreground">
                        {sugestao.descricao || '—'}
                        {sugestao.observacao_aprovacao && (
                          <p className="mt-1 text-xs text-muted-foreground italic">
                            Sugestão de Melhoria: {sugestao.observacao_aprovacao}
                          </p>
                        )}
                      </TableCell>
                      <TableCell>
                        <Badge variant={estado.variant}>
                          {estado.label}
                        </Badge>
                      </TableCell>
                      {mostrarColunaAccoes && (
                        <TableCell className="px-4 text-right">
                          <div className="flex justify-end gap-2">
                            {/* Acções de quem gere */}
                            {canManage && (
                              <>
                                {sugestao.estado_aprovacao === 'melhoria_solicitada' && (
                                  <Button
                                    variant="outline"
                                    size="xs"
                                    className="text-[10px]"
                                    onClick={() => resubmeter(sugestao)}
                                  >
                                    Resubmeter
                                  </Button>
                                )}
                                {sugestao.estado_aprovacao !== 'aprovada' && (
                                  <Button
                                    variant="outline"
                                    size="xs"
                                    className="text-[10px]"
                                    onClick={() => abrirFormularioEdicao(sugestao)}
                                  >
                                    Editar
                                  </Button>
                                )}
                                {sugestao.estado_aprovacao !== 'pendente' && (
                                  <Button
                                    variant="destructive"
                                    size="xs"
                                    className="text-[10px]"
                                    onClick={() => removerSugestao(sugestao)}
                                  >
                                    Remover
                                  </Button>
                                )}
                              </>
                            )}

                            {/* Acções de quem aprova */}
                            {canAprovar && sugestao.estado_aprovacao === 'pendente' && (
                              <>
                                <Button
                                  variant="default"
                                  size="xs"
                                  className="text-[10px]"
                                  onClick={() => aprovar(sugestao)}
                                >
                                  Aprovar
                                </Button>
                                <Button
                                  variant="outline"
                                  size="xs"
                                  className="text-[10px]"
                                  onClick={() => abrirModalObservacao('melhoria', sugestao)}
                                >
                                  Solicitar melhoria
                                </Button>
                                <Button
                                  variant="destructive"
                                  size="xs"
                                  className="text-[10px]"
                                  onClick={() => abrirModalObservacao('rejeitar', sugestao)}
                                >
                                  Rejeitar
                                </Button>
                              </>
                            )}
                          </div>
                        </TableCell>
                      )}
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          )}
        </CardContent>
        <TablePagination pagination={pagination} onPageChange={onPageChange} />
      </Card>

      {/* Modal criação/edição */}
      <Dialog
        open={formularioAberto}
        onOpenChange={(open) => (!open ? fecharFormulario() : setFormularioAberto(true))}
      >
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {sugestaoEmEdicao ? 'Editar sugestão de tema' : 'Nova sugestão de tema'}
            </DialogTitle>
            <DialogDescription>
              Cadastre um tema de referência para os trabalhos da PAP deste curso.
            </DialogDescription>
          </DialogHeader>

          <form onSubmit={submeterSugestao} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="sugestao-titulo">Título do tema</Label>
              <Input
                id="sugestao-titulo"
                value={data.titulo}
                onChange={(e) => setData('titulo', e.target.value)}
                maxLength={255}
                required
                autoFocus
              />
              {errors.titulo && (
                <p className="text-sm text-destructive">{errors.titulo}</p>
              )}
            </div>

            <div className="space-y-2">
              <Label htmlFor="sugestao-descricao">Descrição (opcional)</Label>
              <Textarea
                id="sugestao-descricao"
                value={data.descricao}
                onChange={(e) => setData('descricao', e.target.value)}
                rows={4}
              />
              {errors.descricao && (
                <p className="text-sm text-destructive">{errors.descricao}</p>
              )}
            </div>

            <DialogFooter>
              <Button type="button" variant="outline" onClick={fecharFormulario} disabled={processing}>
                Cancelar
              </Button>
              <Button type="submit" disabled={processing}>
                {processing
                  ? 'A guardar…'
                  : sugestaoEmEdicao
                    ? 'Guardar alterações'
                    : 'Guardar sugestão'}
              </Button>
            </DialogFooter>
          </form>
        </DialogContent>
      </Dialog>

      {/* Modal rejeitar / solicitar melhoria */}
      <Dialog
        open={!!modalObservacao}
        onOpenChange={(open) => (!open ? fecharModalObservacao() : null)}
      >
        <DialogContent className="sm:max-w-lg">
          <DialogHeader>
            <DialogTitle>
              {modalObservacao && MODAL_CONFIG[modalObservacao.tipo].titulo}
            </DialogTitle>
            <DialogDescription>
              {modalObservacao && MODAL_CONFIG[modalObservacao.tipo].descricao}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-2">
            <Label htmlFor="observacao">
              {modalObservacao && MODAL_CONFIG[modalObservacao.tipo].label}
            </Label>
            <Textarea
              id="observacao"
              value={observacao}
              onChange={(e) => setObservacao(e.target.value)}
              rows={4}
              autoFocus
            />
            {submitted && !observacao.trim() && (
              <p className="text-sm text-destructive">Este campo é obrigatório.</p>
            )}
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={fecharModalObservacao} disabled={processando}>
              Cancelar
            </Button>
            <Button
              variant={modalObservacao?.tipo === 'rejeitar' ? 'destructive' : 'default'}
              onClick={submeterObservacao}
              disabled={processando}
            >
              {processando
                ? 'A guardar…'
                : modalObservacao && MODAL_CONFIG[modalObservacao.tipo].confirmar}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}