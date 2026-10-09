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
  FileText as FileIcon,
  Info,
  Loader2,
  CheckCircle2,
  XCircle,
  LockKeyhole,
  Clock8,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Show({ prazo, submissoes }) {
  // ── Loading específico por ação ──
  const [loadingFechar, setLoadingFechar] = useState(false);
  const [loadingProrrogar, setLoadingProrrogar] = useState(false);
  const [avaliandoId, setAvaliandoId] = useState(null); // ID da submissão em avaliação
  const [avaliandoAcao, setAvaliandoAcao] = useState(null); // 'aprovar' | 'rejeitar'

  const [prorrogarDialog, setProrrogarDialog] = useState(false);
  const [novaData, setNovaData] = useState('');
  const [rejectDialog, setRejectDialog] = useState(null);

  const algumLoading =
    loadingFechar || loadingProrrogar || avaliandoId !== null;

  // ── Fechar prazo ──
  const handleFecharPrazo = useCallback(() => {
    if (
      !confirm(
        'Encerrar o prazo "' +
          prazo.titulo +
          '"?\n\n' +
          'Os professores deixam de poder submeter provas.\n' +
          'Todos serão notificados.\n\n' +
          'Esta ação pode ser desfeita prorrogando a duração.',
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
  }, [prazo.id]);

  // ── Prorrogar prazo ──
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

  // ── Avaliar submissão ──
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

      <div className="mx-auto max-w-7xl px-4 py-6">
        {/* Breadcrumb */}
        <nav className="mb-4 flex items-center gap-2 text-sm text-muted-foreground">
          <Link
            href="/dashboard"
            className="transition-colors hover:text-foreground"
          >
            Dashboard
          </Link>
          <span>/</span>
          <Link
            href="/dashboard/diretor/prazos"
            className="transition-colors hover:text-foreground"
          >
            Prazos
          </Link>
          <span>/</span>
          <span className="font-medium text-foreground">{prazo.titulo}</span>
        </nav>

        {/* Cabeçalho */}
        <div className="mb-6 flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-2xl font-bold text-foreground">{prazo.titulo}</h1>
          <div className="flex items-center gap-2">
            <Button variant="outline" asChild>
              <Link href={`/dashboard/diretor/prazos/${prazo.id}/status`}>
                <BarChart3 className="mr-1.5 size-4" />
                Status
              </Link>
            </Button>
            <Button variant="outline" asChild>
              <Link href="/dashboard/diretor/prazos">
                <ArrowLeft className="mr-1.5 size-4" />
                Voltar
              </Link>
            </Button>
          </div>
        </div>

        {/* Card do Prazo */}
        <Card className="mb-6 border-border shadow-sm">
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>Detalhes do Prazo</CardTitle>
            <Badge variant="outline" className={prazo.badge_class}>
              {prazo.status_label}
            </Badge>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-4 pt-4 md:grid-cols-4">
            <div className="flex items-center gap-2 text-sm">
              <Tag className="size-4 text-muted-foreground" />
              <span>
                <strong>Tipo:</strong> {prazo.tipo_prova}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <BookOpen className="size-4 text-muted-foreground" />
              <span>
                <strong>Disciplina:</strong> {prazo.disciplina?.nome || 'Todas'}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Users className="size-4 text-muted-foreground" />
              <span>
                <strong>Classe:</strong> {prazo.classe?.nome || 'Todas'}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="size-4 text-muted-foreground" />
              <span>
                <strong>Início:</strong> {prazo.data_inicio}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Clock className="size-4 text-muted-foreground" />
              <span>
                <strong>Limite:</strong> {prazo.data_limite}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="size-4 text-muted-foreground" />
              <span>
                <strong>Ano Lectivo:</strong> {prazo.ano_letivo}
              </span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Clock className="size-4 text-muted-foreground" />
              <span>
                <strong>Período:</strong> {prazo.periodo}
              </span>
            </div>
            <div className="col-span-2 flex items-center gap-2 text-sm md:col-span-4">
              <MessageSquare className="size-4 text-muted-foreground" />
              <span>
                <strong>Observações:</strong> {prazo.observacoes || 'Nenhuma'}
              </span>
            </div>
          </CardContent>
        </Card>

        {/* Ações do prazo */}
        <div className="mb-6 flex flex-wrap gap-2">
          {prazo.status === 'aberto' && (
            <Button
              variant="destructive"
              onClick={handleFecharPrazo}
              disabled={algumLoading}
            >
              {loadingFechar ? (
                <>
                  <Loader2 className="mr-1.5 size-4 animate-spin" />A
                  encerrar...
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
          >
            {loadingProrrogar ? (
              <>
                <Loader2 className="mr-1.5 size-4 animate-spin" />A prorrogar...
              </>
            ) : (
              <>
                <ClockArrowUp className="mr-1.5 size-4" />
                Prorrogar
              </>
            )}
          </Button>
        </div>

        {/* Lista de Submissões */}
        <div className="mb-4 flex items-center gap-2">
          <FileText className="size-5" />
          <h2 className="text-lg font-semibold">Submissões</h2>
          <Badge variant="secondary">{submissoes.length}</Badge>
        </div>

        {submissoes.length === 0 ? (
          <div className="flex items-center gap-2 rounded-lg border bg-muted/30 p-4 text-muted-foreground">
            <Info className="size-5" />
            <span>Nenhuma submissão ainda.</span>
          </div>
        ) : (
          <div className="space-y-4">
            {submissoes.map((sub) => {
              const estaAvaliando = avaliandoId === sub.id;
              const acaoAtual = estaAvaliando ? avaliandoAcao : null;

              return (
                <Card key={sub.id} className="border-border shadow-sm">
                  <CardContent className="p-4">
                    <div className="flex flex-wrap items-center gap-4">
                      {/* Professor */}
                      <div className="flex min-w-[180px] items-center gap-3">
                        <div className="flex size-10 items-center justify-center rounded-full bg-muted">
                          <User className="size-5 text-muted-foreground" />
                        </div>
                        <div>
                          <p className="font-medium">
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

                      {/* Links arquivos */}
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
                            <FileIcon className="mr-1 size-4" />
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
                              className="bg-green-600 hover:bg-green-700"
                              onClick={() => handleAvaliar(sub.id, 'aprovar')}
                              disabled={algumLoading}
                            >
                              {estaAvaliando && acaoAtual === 'aprovar' ? (
                                <>
                                  <Loader2 className="mr-1 size-4 animate-spin" />
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
                            >
                              {estaAvaliando && acaoAtual === 'rejeitar' ? (
                                <>
                                  <Loader2 className="mr-1 size-4 animate-spin" />
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
                      <div className="mt-3 rounded bg-muted/50 p-2 text-sm">
                        <strong>Parecer:</strong> {sub.parecer}
                      </div>
                    )}
                    {sub.comentario && (
                      <div className="mt-1 text-sm text-muted-foreground">
                        <strong>Comentário do professor:</strong>{' '}
                        {sub.comentario}
                      </div>
                    )}
                  </CardContent>
                </Card>
              );
            })}
          </div>
        )}
      </div>

      {/* Diálogo de Prorrogação */}
      <Dialog open={prorrogarDialog} onOpenChange={setProrrogarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Prorrogar Prazo</DialogTitle>
            <DialogDescription>
              Informe a nova data limite. Os professores serão notificados.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <Label htmlFor="novaData">Nova data limite</Label>
            <Input
              id="novaData"
              type="datetime-local"
              value={novaData}
              onChange={(e) => setNovaData(e.target.value)}
              required
            />
          </div>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={() => setProrrogarDialog(false)}
              disabled={loadingProrrogar}
            >
              Cancelar
            </Button>
            <Button
              variant="default"
              onClick={handleProrrogar}
              disabled={loadingProrrogar}
            >
              {loadingProrrogar ? (
                <>
                  <Loader2 className="mr-1.5 size-4 animate-spin" />A
                  prorrogar...
                </>
              ) : (
                'Prorrogar'
              )}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Diálogo de Rejeição */}
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
          <div className="space-y-2 py-2">
            <Label htmlFor="motivo">Motivo</Label>
            <Input
              id="motivo"
              type="text"
              placeholder="Descreva o motivo..."
              value={rejectDialog?.motivo || ''}
              onChange={(e) =>
                setRejectDialog((prev) => ({ ...prev, motivo: e.target.value }))
              }
              required
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeRejectDialog}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={confirmReject}>
              Rejeitar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
