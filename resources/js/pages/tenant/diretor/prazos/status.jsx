import { Head, router, Link } from '@inertiajs/react';
import { useState, useCallback } from 'react';
import { toast } from 'sonner';
import {
  ArrowLeft,
  Users,
  CheckCircle,
  XCircle,
  User,
  Check,
  X,
  Loader2,
  Clock,
  AlertCircle,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';

export default function Status({ prazo, professores }) {
  // ── Loading por ação específica ──
  const [processing, setProcessing] = useState({});

  // Estado para o diálogo de rejeição de submissão
  const [rejectDialog, setRejectDialog] = useState(null); // { submissaoId, professorNome }
  const [motivoRejeicao, setMotivoRejeicao] = useState('');

  // Estado para o diálogo de recusa de justificativa
  const [justificativaDialog, setJustificativaDialog] = useState(null); // { id, professorNome }
  const [motivoRecusaJustificativa, setMotivoRecusaJustificativa] =
    useState('');

  // ============================================================
  // 1. AVALIAR SUBMISSÃO
  // ============================================================
  const handleAvaliarSubmissao = useCallback((submissaoId, acao, motivo = null) => {
    setProcessing((prev) => ({ ...prev, [`sub_${submissaoId}`]: true }));

      const payload = { acao };
      if (motivo) payload.parecer = motivo;

    router.patch(`/dashboard/diretor/submissoes/${submissaoId}/avaliar`, payload, {
      preserveScroll: true,
      onSuccess: () => {
        toast.success(
          acao === 'aprovar' ? 'Submissão aprovada' : 'Submissão rejeitada',
          {
            description: 'O professor foi notificado.',
          }
        );
        router.reload({ only: ['professores'] });
      },
      onError: () => {
        toast.error('Erro ao avaliar submissão', {
          description: 'Tente novamente em alguns instantes.',
        });
      },
      onFinish: () => {
        setProcessing((prev) => ({ ...prev, [`sub_${submissaoId}`]: false }));
      },
    });
  }, []);

  // ============================================================
  // 2. AVALIAR JUSTIFICATIVA
  // ============================================================
  const handleAvaliarJustificativa = useCallback(
    (justificativaId, status, motivo = null, contexto = {}) => {
      setProcessing((prev) => ({ ...prev, [`just_${justificativaId}`]: true }));

      router.patch(
        `/dashboard/diretor/justificativas/${justificativaId}/avaliar`,
        { status, motivo },
        {
          preserveScroll: true,
          onSuccess: () => {
            toast.success(
              status === 'aceita' ? 'Justificativa aceite' : 'Justificativa recusada',
              {
                description: `Professor: ${contexto.professorNome ?? '—'} • Turma: ${contexto.turmaNome ?? '—'}`,
              }
            );
            router.reload({ only: ['professores'] });
          },
          onError: () => {
            toast.error('Erro ao avaliar justificativa', {
              description: 'Tente novamente em alguns instantes.',
            });
          },
          onFinish: () => {
            setProcessing((prev) => ({ ...prev, [`just_${justificativaId}`]: false }));
            setJustificativaDialog(null);
          },
        }
      );
    },
    []
  );

  // ============================================================
  // 3. ABRIR/FECHAR DIÁLOGOS
  // ============================================================
  const openRejectDialog = (submissaoId, professorNome, turmaNome) => {
    setMotivoRejeicao('');
    setRejectDialog({ submissaoId, professorNome, turmaNome });
  };

  const closeRejectDialog = () => setRejectDialog(null);

  const confirmReject = () => {
    if (!motivoRejeicao.trim()) {
      toast.error('Informe o motivo da rejeição.');
      return;
    }
    const { submissaoId, professorNome, turmaNome } = rejectDialog;
    closeRejectDialog();
    handleAvaliarSubmissao(submissaoId, 'rejeitar', motivoRejeicao.trim());
  };

  const openRejectJustificativaDialog = (id, professorNome, turmaNome) => {
    setMotivoRecusaJustificativa('');
    setJustificativaDialog({ id, professorNome, turmaNome });
  };

  const closeJustificativaDialog = () => setJustificativaDialog(null);

  const confirmRejectJustificativa = () => {
    if (!justificativaDialog) return;
    const { id, professorNome, turmaNome } = justificativaDialog;
    handleAvaliarJustificativa(id, 'recusada', motivoRecusaJustificativa.trim() || null, {
      professorNome,
      turmaNome,
    });
  };

  // ============================================================
  // 4. ESTATÍSTICAS
  // ============================================================
  const total = professores.length;
  const cumpriram = professores.filter((p) => p.submeteu).length;
  const naoCumpriram = total - cumpriram;

  // ============================================================
  // 5. RENDER
  // ============================================================
  return (
    <>
      <Head title={`Status: ${prazo.titulo}`} />

      <div className="mx-auto max-w-7xl space-y-6 p-6">
        {/* Cabeçalho */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <div>
            <h1 className="text-2xl font-bold text-foreground">
              Status do Prazo: <span className="text-primary">{prazo.titulo}</span>
            </h1>
            <p className="text-sm text-muted-foreground mt-1">
              {prazo.disciplina?.nome ?? 'Todas as disciplinas'}
              {prazo.classe?.nome && ` • ${prazo.classe.nome}`}
              {prazo.data_limite && ` • Limite: ${prazo.data_limite}`}
            </p>
          </div>
          <Button variant="outline" asChild>
            <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
              <ArrowLeft className="mr-1.5 size-4" />
              Voltar
            </Link>
          </Button>
        </div>

        {/* Cards de resumo */}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <Card className="border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <Users className="size-8 mr-3 text-muted-foreground" />
              <div>
                <p className="text-sm font-medium text-muted-foreground">
                  Total de atribuições
                </p>
                <p className="text-3xl font-bold">{total}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 text-white shadow-sm">
            <CardContent className="flex items-center p-4">
              <CheckCircle className="mr-3 size-8" />
              <div>
                <CardTitle className="text-sm font-medium">
                  Submeteram
                </CardTitle>
                <p className="text-3xl font-bold">{cumpriram}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <XCircle className="size-8 mr-3 text-red-600" />
              <div>
                <p className="text-sm font-medium text-muted-foreground">
                  Não submeteram
                </p>
                <p className="text-3xl font-bold text-red-600">{naoCumpriram}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Tabela */}
        <Card className="shadow-sm border-border">
          <CardHeader>
            <CardTitle>Lista de Professores</CardTitle>
            <CardDescription>
              Uma linha por professor e turma. Professores que lecionam várias turmas
              aparecem várias vezes — uma por cada turma.
            </CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/50">
                  <TableHead className="px-4">Professor</TableHead>
                  <TableHead className="px-4">Turma</TableHead>
                  <TableHead className="px-4">Submissão</TableHead>
                  <TableHead className="px-4 text-center">Versão</TableHead>
                  <TableHead className="px-4 text-center">Data</TableHead>
                  <TableHead className="px-4">Justificativa</TableHead>
                  <TableHead className="px-4 text-center">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {professores.length === 0 ? (
                  <TableRow>
                    <TableCell
                      colSpan={7}
                      className="text-center py-8 text-muted-foreground"
                    >
                      Nenhum professor atribuído a este prazo.
                    </TableCell>
                  </TableRow>
                ) : (
                  professores.map((prof) => {
                    const rowKey = `${prof.professor_id}-${prof.turma_id ?? 'sem-turma'}`;
                    const isSubmiting = processing[`sub_${prof.submissao_id}`];
                    const isJustifying = prof.justificativa
                      ? processing[`just_${prof.justificativa.id}`]
                      : false;

                    return (
                      <TableRow
                        key={rowKey}
                        className={
                          !prof.submeteu ? 'bg-yellow-50 dark:bg-yellow-950/20' : ''
                        }
                      >
                        {/* Professor */}
                        <TableCell className="px-4">
                          <div className="flex items-center gap-2">
                            <div className="size-8 rounded-full bg-muted flex items-center justify-center shrink-0">
                              <User className="size-4 text-muted-foreground" />
                            </div>
                            <span className="font-medium">{prof.professor_nome}</span>
                          </div>
                        </TableCell>

                        {/* Turma */}
                        <TableCell className="px-4">
                          {prof.turma_nome && prof.turma_nome !== '—' ? (
                            <Badge variant="outline" className="font-medium">
                              <Users className="size-3 mr-1" />
                              {prof.turma_nome}
                            </Badge>
                          ) : (
                            <span className="text-sm text-muted-foreground">—</span>
                          )}
                        </TableCell>

                        {/* Submissão */}
                        <TableCell className="px-4">
                          {prof.submeteu ? (
                            <div className="flex flex-wrap items-center gap-1">
                              <Badge
                                variant="default"
                                className="bg-green-600 hover:bg-green-700"
                              >
                                <CheckCircle className="size-3 mr-1" />
                                Submeteu
                              </Badge>
                              {prof.estado && (
                                <Badge
                                  variant="outline"
                                  className={
                                    prof.estado === 'aprovado'
                                      ? 'border-green-600 text-green-700'
                                      : prof.estado === 'rejeitado'
                                        ? 'border-red-600 text-red-700'
                                        : 'border-yellow-500 text-yellow-700'
                                  }
                                >
                                  {prof.estado === 'aprovado' && (
                                    <Check className="size-3 mr-1" />
                                  )}
                                  {prof.estado === 'rejeitado' && (
                                    <X className="size-3 mr-1" />
                                  )}
                                  {prof.estado_label}
                                </Badge>
                              )}
                            </div>
                          ) : (
                            <Badge variant="destructive">
                              <XCircle className="size-3 mr-1" />
                              Não submeteu
                            </Badge>
                          )}
                        </TableCell>

                        {/* Versão */}
                        <TableCell className="px-4 text-center">
                          {prof.versao ? `v${prof.versao}` : '—'}
                        </TableCell>

                        {/* Data */}
                        <TableCell className="px-4 text-center text-sm text-muted-foreground">
                          {prof.data_submissao || '—'}
                        </TableCell>

                        {/* Justificativa */}
                        <TableCell className="px-4">
                          {prof.justificativa ? (
                            <div className="space-y-1">
                              <Badge
                                variant="outline"
                                className={
                                  prof.justificativa.status === 'pendente'
                                    ? 'border-yellow-500 text-yellow-700'
                                    : prof.justificativa.status === 'aceita'
                                      ? 'border-green-500 text-green-700'
                                      : 'border-red-500 text-red-700'
                                }
                              >
                                {prof.justificativa.status === 'pendente' && (
                                  <AlertCircle className="size-3 mr-1" />
                                )}
                                {prof.justificativa.status === 'aceita' && (
                                  <CheckCircle className="size-3 mr-1" />
                                )}
                                {prof.justificativa.status === 'recusada' && (
                                  <XCircle className="size-3 mr-1" />
                                )}
                                {prof.justificativa.status_label}
                              </Badge>
                              <p className="text-xs text-muted-foreground line-clamp-2">
                                {prof.justificativa.motivo}
                              </p>
                              <p className="text-[10px] text-muted-foreground flex items-center gap-1">
                                <Clock className="size-3" />
                                {prof.justificativa.data_justificativa}
                              </p>
                            </div>
                          ) : (
                            <span className="text-sm text-muted-foreground">—</span>
                          )}
                        </TableCell>

                        {/* Ações */}
                        <TableCell className="px-4 text-center">
                          {/* 1. Submeteu, aguarda avaliação */}
                          {prof.submeteu && prof.estado === 'pendente' && (
                            <div className="flex justify-center gap-2">
                              <Button
                                variant="default"
                                size="sm"
                                className="bg-green-600 hover:bg-green-700"
                                onClick={() =>
                                  handleAvaliarSubmissao(prof.submissao_id, 'aprovar')
                                }
                                disabled={isSubmiting}
                              >
                                {isSubmiting ? (
                                  <Loader2 className="size-4 animate-spin" />
                                ) : (
                                  <>
                                    <Check className="size-4 mr-1" />
                                    Aprovar
                                  </>
                                )}
                              </Button>
                              <Button
                                variant="destructive"
                                size="sm"
                                onClick={() =>
                                  openRejectDialog(
                                    prof.submissao_id,
                                    prof.professor_nome,
                                    prof.turma_nome
                                  )
                                }
                                disabled={isSubmiting}
                              >
                                {isSubmiting ? (
                                  <Loader2 className="size-4 animate-spin" />
                                ) : (
                                  <>
                                    <X className="size-4 mr-1" />
                                    Rejeitar
                                  </>
                                )}
                              </Button>
                            </div>
                          )}

                          {/* 2. Submeteu e já foi avaliado */}
                          {prof.submeteu && prof.estado !== 'pendente' && (
                            <span className="text-sm text-muted-foreground flex items-center justify-center gap-1">
                              <CheckCircle className="size-4" />
                              Avaliado
                            </span>
                          )}

                          {/* 3. Não submeteu, justificativa pendente */}
                          {!prof.submeteu &&
                            prof.justificativa &&
                            prof.justificativa.status === 'pendente' && (
                              <div className="flex flex-col items-center gap-1">
                                <Badge
                                  variant="outline"
                                  className="border-yellow-500 text-yellow-700"
                                >
                                  <AlertCircle className="size-3 mr-1" />
                                  Aguardando análise
                                </Badge>
                                <div className="flex gap-1">
                                  <Button
                                    size="sm"
                                    variant="default"
                                    className="bg-green-600 hover:bg-green-700 text-xs h-7"
                                    onClick={() =>
                                      handleAvaliarJustificativa(
                                        prof.justificativa.id,
                                        'aceita',
                                        null,
                                        {
                                          professorNome: prof.professor_nome,
                                          turmaNome: prof.turma_nome,
                                        }
                                      )
                                    }
                                    disabled={isJustifying}
                                  >
                                    {isJustifying ? (
                                      <Loader2 className="size-3 animate-spin" />
                                    ) : (
                                      <>
                                        <Check className="size-3 mr-1" />
                                        Aceitar
                                      </>
                                    )}
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="destructive"
                                    className="text-xs h-7"
                                    onClick={() =>
                                      openRejectJustificativaDialog(
                                        prof.justificativa.id,
                                        prof.professor_nome,
                                        prof.turma_nome
                                      )
                                    }
                                    disabled={isJustifying}
                                  >
                                    {isJustifying ? (
                                      <Loader2 className="size-3 animate-spin" />
                                    ) : (
                                      <>
                                        <X className="size-3 mr-1" />
                                        Recusar
                                      </>
                                    )}
                                  </Button>
                                </div>
                              </div>
                            )}

                          {/* 4. Não submeteu, justificativa já avaliada */}
                          {!prof.submeteu &&
                            prof.justificativa &&
                            prof.justificativa.status !== 'pendente' && (
                              <span className="text-sm text-muted-foreground flex items-center justify-center gap-1">
                                {prof.justificativa.status === 'aceita' ? (
                                  <CheckCircle className="size-4 text-green-600" />
                                ) : (
                                  <XCircle className="size-4 text-red-600" />
                                )}
                                {prof.justificativa.status_label}
                              </span>
                            )}

                          {/* 5. Não submeteu, sem justificativa */}
                          {!prof.submeteu && !prof.justificativa && (
                            <span className="text-sm text-muted-foreground">
                              Sem justificativa
                            </span>
                          )}
                        </TableCell>
                      </TableRow>
                    );
                  })
                )}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      {/* ============================================================
          DIÁLOGO 1: Rejeitar Submissão
          ============================================================ */}
      <Dialog
        open={!!rejectDialog}
        onOpenChange={(open) => !open && closeRejectDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <XCircle className="size-5 text-destructive" />
              Rejeitar Submissão
            </DialogTitle>
            <DialogDescription>
              <strong>Professor:</strong> {rejectDialog?.professorNome}
              <br />
              <strong>Turma:</strong> {rejectDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <label htmlFor="motivoRejeicao" className="text-sm font-medium">
              Motivo da rejeição <span className="text-destructive">*</span>
            </label>
            <Textarea
              id="motivoRejeicao"
              rows={3}
              placeholder="Explique porque a submissão está a ser rejeitada..."
              value={motivoRejeicao}
              onChange={(e) => setMotivoRejeicao(e.target.value)}
              required
            />
            <p className="text-xs text-muted-foreground">
              O professor será notificado com este parecer.
            </p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeRejectDialog}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={confirmReject}>
              Rejeitar Submissão
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ============================================================
          DIÁLOGO 2: Recusar Justificativa
          ============================================================ */}
      <Dialog
        open={!!justificativaDialog}
        onOpenChange={(open) => !open && closeJustificativaDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <XCircle className="size-5 text-destructive" />
              Recusar Justificativa
            </DialogTitle>
            <DialogDescription>
              <strong>Professor:</strong> {justificativaDialog?.professorNome}
              <br />
              <strong>Turma:</strong> {justificativaDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <label
              htmlFor="motivoRecusaJustificativa"
              className="text-sm font-medium"
            >
              Motivo da recusa{' '}
              <span className="text-muted-foreground">(opcional)</span>
            </label>
            <Textarea
              id="motivoRecusaJustificativa"
              rows={3}
              placeholder="Explique porque a justificativa foi recusada..."
              value={motivoRecusaJustificativa}
              onChange={(e) => setMotivoRecusaJustificativa(e.target.value)}
            />
            <p className="text-xs text-muted-foreground">
              O professor será notificado com este parecer.
            </p>
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeJustificativaDialog}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={confirmRejectJustificativa}>
              Recusar Justificativa
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
