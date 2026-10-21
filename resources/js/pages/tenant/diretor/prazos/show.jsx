import { Head, router, Link } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { toast } from 'sonner';
import {
  ArrowLeft,
  BarChart3,
  Calendar,
  Clock,
  BookOpen,
  Users,
  Tag,
  MessageSquare,
  FileText,
  Lock,
  ClockArrowUp,
  User,
  Check,
  X,
  File,
  Info,
} from 'lucide-react';

import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Field, FieldError, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/empty-state';
import { Spinner } from '@/components/spinner';

export default function Show({ prazo, submissoes }) {
  const [loadingFechar, setLoadingFechar] = useState(false);
  const [loadingProrrogar, setLoadingProrrogar] = useState(false);
  const [avaliandoId, setAvaliandoId] = useState(null);
  const [avaliandoAcao, setAvaliandoAcao] = useState(null);

  const [prorrogarDialog, setProrrogarDialog] = useState(false);
  const [novaData, setNovaData] = useState('');
  const [rejectDialog, setRejectDialog] = useState(null);

  const algumLoading =
    loadingFechar || loadingProrrogar || avaliandoId !== null;

  const handleFecharPrazo = useCallback(() => {
    if (
      !confirm(
        `Encerrar o prazo "${prazo.titulo}"?\n\nOs professores deixam de poder submeter provas.\nTodos serão notificados.\n\nEsta ação pode ser desfeita prorrogando a duração.`,
      )
    )
      return;
    setLoadingFechar(true);
    router.post(
      `/dashboard/diretor/prazos/${prazo.id}/fechar`,
      {},
      {
        onSuccess: () => {
          toast.success('Prazo encerrado', {
            description:
              'Os professores foram notificados. Já não é possível submeter provas.',
          });
          router.reload();
        },
        onError: () => {
          toast.error('Erro ao encerrar prazo', {
            description: 'Tente novamente em alguns instantes.',
          });
        },
        onFinish: () => setLoadingFechar(false),
      },
    );
  }, [prazo.id, prazo.titulo]);

  const handleProrrogar = useCallback(() => {
    if (!novaData) {
      toast.error('Informe a nova data limite.');
      return;
    }

    setLoadingProrrogar(true);
    router.post(
      `/dashboard/diretor/prazos/${prazo.id}/prorrogar`,
      { nova_data_limite: novaData },
      {
        onSuccess: () => {
          toast.success('Prazo prorrogado', {
            description: `Nova data limite: ${new Date(novaData).toLocaleString('pt-AO')}`,
          });
          setProrrogarDialog(false);
          setNovaData('');
          router.reload();
        },
        onError: () => {
          toast.error('Erro ao prorrogar prazo');
        },
        onFinish: () => setLoadingProrrogar(false),
      },
    );
  }, [prazo.id, novaData]);

  const handleAvaliar = useCallback((submissaoId, acao, motivo = null) => {
    setAvaliandoId(submissaoId);
    setAvaliandoAcao(acao);

    const payload = { acao };
    if (motivo) payload.parecer = motivo;

    router.patch(
      `/dashboard/diretor/submissoes/${submissaoId}/avaliar`,
      payload,
      {
        onSuccess: () => {
          toast.success(
            acao === 'aprovar' ? 'Submissão aprovada' : 'Submissão rejeitada',
            {
              description:
                acao === 'aprovar'
                  ? 'O professor foi notificado da aprovação.'
                  : 'O professor foi notificado com o motivo da rejeição.',
            },
          );
          router.reload();
        },
        onError: () => {
          toast.error('Erro ao avaliar submissão');
        },
        onFinish: () => {
          setAvaliandoId(null);
          setAvaliandoAcao(null);
        },
      },
    );
  }, []);

  const openRejectDialog = (submissaoId) => {
    setRejectDialog({ submissaoId, motivo: '' });
  };

  const closeRejectDialog = () => setRejectDialog(null);

  const confirmReject = () => {
    if (!rejectDialog?.motivo?.trim()) {
      toast.error('Informe o motivo da rejeição.');
      return;
    }
    const { submissaoId, motivo } = rejectDialog;
    closeRejectDialog();
    handleAvaliar(submissaoId, 'rejeitar', motivo.trim());
  };

  return (
    <>
      <Head title={`Prazo: ${prazo.titulo}`} />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header principal */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b border-foreground/10 md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <Link
                        href="/dashboard/diretor/prazos"
                        className="line-clamp-2 text-sm font-semibold text-primary"
                      >
                        Prazos
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      {prazo.titulo}
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Detalhes do prazo e submissões dos professores.
              </CardDescription>
            </div>
            <CardAction className="flex flex-wrap items-center gap-2">
              <Badge variant="outline" className={prazo.badge_class}>
                {prazo.status_label}
              </Badge>
              <Button variant="outline" size="sm" asChild>
                <Link href={`/dashboard/diretor/prazos/${prazo.id}/status`}>
                  <BarChart3 className="mr-1.5 size-4" />
                  Status
                </Link>
              </Button>
              <Button variant="outline" size="sm" asChild>
                <Link href="/dashboard/diretor/prazos">
                  <ArrowLeft className="mr-1.5 size-4" />
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>

          <CardContent className="grid grid-cols-1 gap-3 p-4 sm:grid-cols-2 md:grid-cols-4">
            <div className="flex items-start gap-2 text-sm">
              <Tag className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Tipo
                </p>
                <p className="truncate font-medium">{prazo.tipo_prova}</p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <BookOpen className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Disciplina
                </p>
                <p className="truncate font-medium">
                  {prazo.disciplina?.nome || 'Todas'}
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <Users className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Classe
                </p>
                <p className="truncate font-medium">
                  {prazo.classe?.nome || 'Todas'}
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <Calendar className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Início
                </p>
                <p className="truncate font-medium whitespace-nowrap">
                  {prazo.data_inicio}
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <Clock className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Limite
                </p>
                <p className="truncate font-medium whitespace-nowrap">
                  {prazo.data_limite}
                </p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <Calendar className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Ano Letivo
                </p>
                <p className="truncate font-medium">{prazo.ano_letivo}</p>
              </div>
            </div>
            <div className="flex items-start gap-2 text-sm">
              <Clock className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Período
                </p>
                <p className="truncate font-medium">{prazo.periodo}</p>
              </div>
            </div>
            <div className="col-span-1 flex items-start gap-2 text-sm sm:col-span-2 md:col-span-4">
              <MessageSquare className="mt-0.5 size-4 shrink-0 text-muted-foreground" />
              <div className="min-w-0">
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Observações
                </p>
                <p className="whitespace-pre-wrap font-medium">
                  {prazo.observacoes || 'Nenhuma'}
                </p>
              </div>
            </div>
          </CardContent>

          {/* Ações do prazo */}
          <div className="flex flex-wrap items-center gap-2 border-t px-4 py-3">
            {prazo.status === 'aberto' && (
              <Button
                variant="destructive"
                onClick={handleFecharPrazo}
                disabled={algumLoading}
                className="hover:cursor-pointer"
              >
                {loadingFechar ? (
                  <>
                    <Spinner />
                    A encerrar...
                  </>
                ) : (
                  <>
                    <Lock className="mr-1.5 size-4" />
                    Encerrar prazo
                  </>
                )}
              </Button>
            )}

            <Button
              variant="default"
              onClick={() => setProrrogarDialog(true)}
              disabled={algumLoading}
              className="hover:cursor-pointer"
            >
              {loadingProrrogar ? (
                <>
                  <Spinner />
                  A prorrogar...
                </>
              ) : (
                <>
                  <ClockArrowUp className="mr-1.5 size-4" />
                  Prorrogar
                </>
              )}
            </Button>
          </div>
        </Card>

        {/* Submissões */}
        <Card className="gap-0 pb-0">
          <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <CardTitle className="flex items-center gap-2">
                <FileText className="size-5" />
                Submissões
              </CardTitle>
              <CardDescription>
                Aprovar ou rejeitar as provas submetidas pelos professores.
              </CardDescription>
            </div>
            <CardAction>
              <Badge variant="secondary">{submissoes.length}</Badge>
            </CardAction>
          </CardHeader>

          <CardContent className="p-0!">
            {submissoes.length === 0 ? (
              <EmptyState
                variant="table"
                icon={Info}
                title="Nenhuma submissão"
                description="Ainda não há provas submetidas para este prazo."
              />
            ) : (
              <div className="divide-y">
                {submissoes.map((sub) => {
                  const estaAvaliando = avaliandoId === sub.id;
                  const acaoAtual = estaAvaliando ? avaliandoAcao : null;

                  return (
                    <div key={sub.id} className="p-4">
                      <div className="flex flex-wrap items-center gap-3">
                        {/* Professor */}
                        <div className="flex min-w-[180px] items-center gap-3">
                          <div className="flex size-10 items-center justify-center bg-muted">
                            <User className="size-5 text-muted-foreground" />
                          </div>
                          <div className="min-w-0">
                            <p className="truncate font-medium">
                              {sub.professor?.nome || 'Professor'}
                            </p>
                            <p className="text-xs text-muted-foreground">
                              Versão {sub.versao}
                            </p>
                          </div>
                        </div>

                        {/* Estado */}
                        <Badge variant="outline" className={sub.badge_class}>
                          {sub.estado_label}
                        </Badge>

                        {/* Ficheiros */}
                        <div className="flex gap-1">
                          <Button variant="outline" size="sm" asChild>
                            <a
                              href={sub.url_prova}
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              <File className="mr-1 size-4" />
                              Prova
                            </a>
                          </Button>
                          <Button variant="outline" size="sm" asChild>
                            <a
                              href={sub.url_chave}
                              target="_blank"
                              rel="noopener noreferrer"
                            >
                              <File className="mr-1 size-4" />
                              Chave
                            </a>
                          </Button>
                        </div>

                        {/* Ações */}
                        <div className="ml-auto flex items-center gap-2">
                          {sub.estado === 'pendente' && (
                            <>
                              <Button
                                variant="default"
                                size="sm"
                                className="bg-green-600 hover:bg-green-700 hover:cursor-pointer"
                                onClick={() =>
                                  handleAvaliar(sub.id, 'aprovar')
                                }
                                disabled={algumLoading}
                              >
                                {estaAvaliando && acaoAtual === 'aprovar' ? (
                                  <>
                                    <Spinner />
                                    A aprovar...
                                  </>
                                ) : (
                                  <>
                                    <Check className="mr-1 size-4" />
                                    Aprovar
                                  </>
                                )}
                              </Button>
                              <Button
                                variant="destructive"
                                size="sm"
                                onClick={() => openRejectDialog(sub.id)}
                                disabled={algumLoading}
                                className="hover:cursor-pointer"
                              >
                                {estaAvaliando && acaoAtual === 'rejeitar' ? (
                                  <>
                                    <Spinner />
                                    A rejeitar...
                                  </>
                                ) : (
                                  <>
                                    <X className="mr-1 size-4" />
                                    Rejeitar
                                  </>
                                )}
                              </Button>
                            </>
                          )}
                          {sub.estado !== 'pendente' && (
                            <span className="flex items-center gap-1 text-sm text-muted-foreground">
                              {sub.estado === 'aprovado' ? (
                                <Check className="size-4 text-green-600" />
                              ) : (
                                <X className="size-4 text-red-600" />
                              )}
                              Avaliado
                            </span>
                          )}
                        </div>
                      </div>

                      {sub.parecer && (
                        <div className="mt-3 bg-muted/50 p-2 text-sm">
                          <strong>Parecer:</strong> {sub.parecer}
                        </div>
                      )}
                      {sub.comentario && (
                        <div className="mt-1 text-sm text-muted-foreground">
                          <strong>Comentário do professor:</strong>{' '}
                          {sub.comentario}
                        </div>
                      )}
                    </div>
                  );
                })}
              </div>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Dialog prorrogar */}
      <Dialog open={prorrogarDialog} onOpenChange={setProrrogarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Prorrogar Prazo</DialogTitle>
            <DialogDescription>
              Informe a nova data limite. Os professores serão notificados.
            </DialogDescription>
          </DialogHeader>
          <Field>
            <FieldLabel htmlFor="novaData">Nova data limite</FieldLabel>
            <Input
              id="novaData"
              type="datetime-local"
              value={novaData}
              onChange={(e) => setNovaData(e.target.value)}
              required
            />
          </Field>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setProrrogarDialog(false)}
              disabled={loadingProrrogar}
              className="hover:cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              variant="default"
              onClick={handleProrrogar}
              disabled={loadingProrrogar}
              className="hover:cursor-pointer"
            >
              {loadingProrrogar ? (
                <>
                  <Spinner />
                  A prorrogar...
                </>
              ) : (
                'Prorrogar'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Dialog rejeitar */}
      <Dialog
        open={!!rejectDialog}
        onOpenChange={(open) => !open && closeRejectDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Rejeitar Submissão</DialogTitle>
            <DialogDescription>
              Informe o motivo da rejeição. O professor será notificado.
            </DialogDescription>
          </DialogHeader>
          <Field>
            <FieldLabel htmlFor="motivo">Motivo</FieldLabel>
            <Input
              id="motivo"
              type="text"
              placeholder="Descreva o motivo..."
              value={rejectDialog?.motivo || ''}
              onChange={(e) =>
                setRejectDialog((prev) => ({
                  ...prev,
                  motivo: e.target.value,
                }))
              }
              required
            />
          </Field>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={closeRejectDialog}
              className="hover:cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              variant="destructive"
              onClick={confirmReject}
              className="hover:cursor-pointer"
            >
              Rejeitar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}