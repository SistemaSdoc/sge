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
  CalendarClock,
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
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
  const [processing, setProcessing] = useState({});

  const [rejectDialog, setRejectDialog] = useState(null);
  const [motivoRejeicao, setMotivoRejeicao] = useState('');

  const [justificativaDialog, setJustificativaDialog] = useState(null);
  const [motivoRecusaJustificativa, setMotivoRecusaJustificativa] =
    useState('');
  const [diasPrazoExtra, setDiasPrazoExtra] = useState(3);

  // ============================================================
  // 1. AVALIAR SUBMISSÃO
  // ============================================================
  const handleAvaliarSubmissao = useCallback(
    (submissaoId, acao, motivo = null) => {
      setProcessing((prev) => ({ ...prev, [`sub_${submissaoId}`]: true }));

      const payload = { acao };
      if (motivo) payload.parecer = motivo;

      router.patch(
        `/dashboard/diretor/submissoes/${submissaoId}/avaliar`,
        payload,
        {
          preserveScroll: true,
          preserveState: false, // ✅ NOVO
          onSuccess: () => {
            toast.success(
              acao === 'aprovar' ? 'Submissão aprovada' : 'Submissão rejeitada',
              { description: 'O professor foi notificado.' },
            );
            router.reload({
              only: ['professores'],
              preserveScroll: true,
              preserveState: false, // ✅ NOVO
            });
          },
          onError: () => {
            toast.error('Erro ao avaliar submissão');
          },
          onFinish: () => {
            setProcessing((prev) => ({
              ...prev,
              [`sub_${submissaoId}`]: false,
            }));
          },
        },
      );
    },
    [],
  );

  // ============================================================
  // 2. AVALIAR JUSTIFICATIVA
  // ============================================================
  const handleAvaliarJustificativa = useCallback(
    (justificativaId, status, motivo = null, contexto = {}, dias = null) => {
      setProcessing((prev) => ({ ...prev, [`just_${justificativaId}`]: true }));

      const payload = { status, motivo };
      if (status === 'aceita' && dias) {
        payload.dias_prazo_extra = dias;
      }

      // Guarda os valores antes de resetar
      const professorNome = contexto.professorNome ?? '—';
      const turmaNome = contexto.turmaNome ?? '—';
      const diasUsados = dias ?? 3;

      router.patch(
        `/dashboard/diretor/justificativas/${justificativaId}/avaliar`,
        payload,
        {
          preserveScroll: true,
          preserveState: false, // ✅ NOVO
          onSuccess: () => {
            if (status === 'aceita') {
              toast.success('Justificativa aceite', {
                description: `${professorNome} • ${turmaNome} • ${diasUsados} dias de prazo extra`,
              });
            } else {
              toast.success('Justificativa recusada', {
                description: `${professorNome} • ${turmaNome}`,
              });
            }
            router.reload({
              only: ['professores'],
              preserveScroll: true,
              preserveState: false, // ✅ NOVO
            });
          },
          onError: () => {
            toast.error('Erro ao avaliar justificativa');
          },
          onFinish: () => {
            setProcessing((prev) => ({
              ...prev,
              [`just_${justificativaId}`]: false,
            }));
            setJustificativaDialog(null);
            setDiasPrazoExtra(3);
          },
        },
      );
    },
    [],
  );

  // ============================================================
  // 3. DIÁLOGOS
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
    const { submissaoId } = rejectDialog;
    closeRejectDialog();
    handleAvaliarSubmissao(submissaoId, 'rejeitar', motivoRejeicao.trim());
  };

  const openRejectJustificativaDialog = (id, professorNome, turmaNome) => {
    setMotivoRecusaJustificativa('');
    setJustificativaDialog({ id, professorNome, turmaNome, aceitar: false });
  };

  const closeJustificativaDialog = () => setJustificativaDialog(null);

  const confirmRejectJustificativa = () => {
    if (!justificativaDialog) return;
    const { id, professorNome, turmaNome } = justificativaDialog;
    handleAvaliarJustificativa(
      id,
      'recusada',
      motivoRecusaJustificativa.trim() || null,
      {
        professorNome,
        turmaNome,
      },
    );
  };

  const confirmAceitar = () => {
    if (!justificativaDialog) return;

    if (diasPrazoExtra < 1 || diasPrazoExtra > 30) {
      toast.error('O prazo extra deve ser entre 1 e 30 dias.');
      return;
    }

    const { id, professorNome, turmaNome } = justificativaDialog;
    handleAvaliarJustificativa(
      id,
      'aceita',
      null,
      { professorNome, turmaNome },
      diasPrazoExtra,
    );
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
              Status do Prazo:{' '}
              <span className="text-primary">{prazo.titulo}</span>
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
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
              <Users className="mr-3 size-8 text-muted-foreground" />
              <div>
                <p className="text-sm font-medium text-muted-foreground">
                  Total
                </p>
                <p className="text-3xl font-bold">{total}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <CheckCircle className="mr-3 size-8 text-green-600" />
              <div>
                <p className="text-sm font-medium text-muted-foreground">
                  Submeteram
                </p>
                <p className="text-3xl font-bold text-green-600">{cumpriram}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <XCircle className="mr-3 size-8 text-red-600" />
              <div>
                <p className="text-sm font-medium text-muted-foreground">
                  Não submeteram
                </p>
                <p className="text-3xl font-bold text-red-600">
                  {naoCumpriram}
                </p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Tabela */}
        <Card className="border-border shadow-sm">
          <CardHeader>
            <CardTitle>Lista de Professores</CardTitle>
            <CardDescription>
              Uma linha por professor e turma. Aceitar justificativa concede
              prazo extra automaticamente.
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
                      className="py-8 text-center text-muted-foreground"
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
                          !prof.submeteu
                            ? 'bg-yellow-50 dark:bg-yellow-950/20'
                            : ''
                        }
                      >
                        <TableCell className="px-4">
                          <div className="flex items-center gap-2">
                            <div className="flex size-8 items-center justify-center rounded-full bg-muted">
                              <User className="size-4 text-muted-foreground" />
                            </div>
                            <span className="font-medium">
                              {prof.professor_nome}
                            </span>
                          </div>
                        </TableCell>

                        <TableCell className="px-4">
                          {prof.turma_nome && prof.turma_nome !== '—' ? (
                            <Badge variant="outline" className="font-medium">
                              <Users className="mr-1 size-3" />
                              {prof.turma_nome}
                            </Badge>
                          ) : (
                            <span className="text-sm text-muted-foreground">
                              —
                            </span>
                          )}
                        </TableCell>

                        <TableCell className="px-4">
                          {prof.submeteu ? (
                            <div className="flex flex-wrap items-center gap-1">
                              <Badge
                                variant="default"
                                className="bg-green-600 hover:bg-green-700"
                              >
                                <CheckCircle className="mr-1 size-3" />
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
                                    <Check className="mr-1 size-3" />
                                  )}
                                  {prof.estado === 'rejeitado' && (
                                    <X className="mr-1 size-3" />
                                  )}
                                  {prof.estado_label}
                                </Badge>
                              )}
                            </div>
                          ) : (
                            <Badge variant="destructive">
                              <XCircle className="mr-1 size-3" />
                              Não submeteu
                            </Badge>
                          )}
                        </TableCell>

                        <TableCell className="px-4 text-center">
                          {prof.versao ? `v${prof.versao}` : '—'}
                        </TableCell>

                        <TableCell className="px-4 text-center text-sm text-muted-foreground">
                          {prof.data_submissao || '—'}
                        </TableCell>

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
                                  <AlertCircle className="mr-1 size-3" />
                                )}
                                {prof.justificativa.status === 'aceita' && (
                                  <CheckCircle className="mr-1 size-3" />
                                )}
                                {prof.justificativa.status === 'recusada' && (
                                  <XCircle className="mr-1 size-3" />
                                )}
                                {prof.justificativa.status_label}
                              </Badge>
                              <p className="line-clamp-2 text-xs text-muted-foreground">
                                {prof.justificativa.motivo}
                              </p>
                              <p className="flex items-center gap-1 text-[10px] text-muted-foreground">
                                <Clock className="size-3" />
                                {prof.justificativa.data_justificativa}
                              </p>
                            </div>
                          ) : (
                            <span className="text-sm text-muted-foreground">
                              —
                            </span>
                          )}
                        </TableCell>

                        <TableCell className="px-4 text-center">
                          {/* Submeteu pendente */}
                          {prof.submeteu && prof.estado === 'pendente' && (
                            <div className="flex justify-center gap-2">
                              <Button
                                variant="default"
                                size="sm"
                                className="bg-green-600 hover:bg-green-700"
                                onClick={() =>
                                  handleAvaliarSubmissao(
                                    prof.submissao_id,
                                    'aprovar',
                                  )
                                }
                                disabled={isSubmiting}
                              >
                                {isSubmiting ? (
                                  <Loader2 className="size-4 animate-spin" />
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
                                onClick={() =>
                                  openRejectDialog(
                                    prof.submissao_id,
                                    prof.professor_nome,
                                    prof.turma_nome,
                                  )
                                }
                                disabled={isSubmiting}
                              >
                                <X className="mr-1 size-4" />
                                Rejeitar
                              </Button>
                            </div>
                          )}

                          {prof.submeteu && prof.estado !== 'pendente' && (
                            <span className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                              <CheckCircle className="size-4" />
                              Avaliado
                            </span>
                          )}

                          {/* Justificativa pendente → aceitar/recusar */}
                          {!prof.submeteu &&
                            prof.justificativa &&
                            prof.justificativa.status === 'pendente' && (
                              <div className="flex flex-col items-center gap-1">
                                <div className="flex gap-1">
                                  <Button
                                    size="sm"
                                    variant="default"
                                    className="h-7 bg-green-600 text-xs hover:bg-green-700"
                                    onClick={() =>
                                      setJustificativaDialog({
                                        id: prof.justificativa.id,
                                        professorNome: prof.professor_nome,
                                        turmaNome: prof.turma_nome,
                                        aceitar: true,
                                      })
                                    }
                                    disabled={isJustifying}
                                  >
                                    {isJustifying ? (
                                      <Loader2 className="size-3 animate-spin" />
                                    ) : (
                                      <>
                                        <Check className="mr-1 size-3" />
                                        Aceitar
                                      </>
                                    )}
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="destructive"
                                    className="h-7 text-xs"
                                    onClick={() =>
                                      openRejectJustificativaDialog(
                                        prof.justificativa.id,
                                        prof.professor_nome,
                                        prof.turma_nome,
                                      )
                                    }
                                    disabled={isJustifying}
                                  >
                                    <X className="mr-1 size-3" />
                                    Recusar
                                  </Button>
                                </div>
                              </div>
                            )}

                          {!prof.submeteu &&
                            prof.justificativa &&
                            prof.justificativa.status !== 'pendente' && (
                              <span className="flex items-center justify-center gap-1 text-sm text-muted-foreground">
                                {prof.justificativa.status === 'aceita' ? (
                                  <CheckCircle className="size-4 text-green-600" />
                                ) : (
                                  <XCircle className="size-4 text-red-600" />
                                )}
                                {prof.justificativa.status_label}
                              </span>
                            )}

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

      {/* Diálogo: Rejeitar submissão */}
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
            <label className="text-sm font-medium">
              Motivo <span className="text-destructive">*</span>
            </label>
            <Textarea
              rows={3}
              value={motivoRejeicao}
              onChange={(e) => setMotivoRejeicao(e.target.value)}
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

      {/* Diálogo: Recusar justificativa */}
      <Dialog
        open={!!justificativaDialog && !justificativaDialog.aceitar}
        onOpenChange={(open) => !open && closeJustificativaDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <XCircle className="size-5 text-destructive" />
              Recusar Justificativa
            </DialogTitle>
            <DialogDescription>
              <strong>{justificativaDialog?.professorNome}</strong> •{' '}
              {justificativaDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <label className="text-sm font-medium">
              Motivo <span className="text-muted-foreground">(opcional)</span>
            </label>
            <Textarea
              rows={3}
              value={motivoRecusaJustificativa}
              onChange={(e) => setMotivoRecusaJustificativa(e.target.value)}
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeJustificativaDialog}>
              Cancelar
            </Button>
            <Button variant="destructive" onClick={confirmRejectJustificativa}>
              Recusar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Diálogo: Aceitar justificativa com escolha de dias */}
      <Dialog
        open={!!justificativaDialog && justificativaDialog.aceitar === true}
        onOpenChange={(open) => !open && closeJustificativaDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle className="flex items-center gap-2">
              <CalendarClock className="size-5 text-green-600" />
              Aceitar Justificativa
            </DialogTitle>
            <DialogDescription>
              <strong>{justificativaDialog?.professorNome}</strong> •{' '}
              {justificativaDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3 py-2">
            <div className="rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950/20">
              <p className="text-sm text-green-800 dark:text-green-300">
                Ao aceitar, será concedido um prazo extra para o professor
                submeter a prova <strong>desta turma</strong>.
              </p>
            </div>

            <div className="space-y-1.5">
              <label className="text-sm font-medium">
                Dias de prazo extra <span className="text-destructive">*</span>
              </label>
              <Input
                type="number"
                min={1}
                max={30}
                value={diasPrazoExtra}
                onChange={(e) => setDiasPrazoExtra(Number(e.target.value))}
              />
              <p className="text-xs text-muted-foreground">
                Entre 1 e 30 dias. Padrão: 3 dias.
              </p>
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={closeJustificativaDialog}>
              Cancelar
            </Button>
            <Button
              className="bg-green-600 hover:bg-green-700"
              onClick={confirmAceitar}
            >
              <Check className="mr-1.5 size-4" />
              Aceitar e conceder {diasPrazoExtra} dias
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
