import { Head, router, Link } from '@inertiajs/react';
import { useState, useCallback, useMemo } from 'react';
import { toast } from 'sonner';

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
import { Field, FieldLabel } from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { EmptyState } from '@/components/empty-state';
import { Spinner } from '@/components/spinner';
import TablePagination from '@/components/table-pagination';

const POR_PAGINA = 15;

export default function Status({ prazo, professores = [] }) {
  const [processing, setProcessing] = useState({});

  const [rejectDialog, setRejectDialog] = useState(null);
  const [motivoRejeicao, setMotivoRejeicao] = useState('');

  const [justificativaDialog, setJustificativaDialog] = useState(null);
  const [motivoRecusaJustificativa, setMotivoRecusaJustificativa] =
    useState('');
  const [diasPrazoExtra, setDiasPrazoExtra] = useState(3);

  // ────────────────────────────────────────────────────────
  // 1. AVALIAR SUBMISSÃO
  // ────────────────────────────────────────────────────────
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
          preserveState: false,
          onSuccess: () => {
            toast.success(
              acao === 'aprovar'
                ? 'Submissão aprovada'
                : 'Submissão rejeitada',
              { description: 'O professor foi notificado.' },
            );
            router.reload({
              only: ['professores'],
              preserveScroll: true,
              preserveState: false,
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

  // ────────────────────────────────────────────────────────
  // 2. AVALIAR JUSTIFICATIVA
  // ────────────────────────────────────────────────────────
  const handleAvaliarJustificativa = useCallback(
    (justificativaId, status, motivo = null, contexto = {}, dias = null) => {
      setProcessing((prev) => ({
        ...prev,
        [`just_${justificativaId}`]: true,
      }));

      const payload = { status, motivo };
      if (status === 'aceita' && dias) {
        payload.dias_prazo_extra = dias;
      }

      const professorNome = contexto.professorNome ?? '—';
      const turmaNome = contexto.turmaNome ?? '—';
      const diasUsados = dias ?? 3;

      router.patch(
        `/dashboard/diretor/justificativas/${justificativaId}/avaliar`,
        payload,
        {
          preserveScroll: true,
          preserveState: false,
          onSuccess: () => {
            if (status === 'aceita') {
              toast.success('Justificativa aceita', {
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
              preserveState: false,
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

  // ────────────────────────────────────────────────────────
  // 3. DIÁLOGOS
  // ────────────────────────────────────────────────────────
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
      { professorNome, turmaNome },
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

  // ────────────────────────────────────────────────────────
  // 4. ESTATÍSTICAS
  // ────────────────────────────────────────────────────────
  const total = professores.length;
  const cumpriram = professores.filter((p) => p.submeteu).length;
  const naoCumpriram = total - cumpriram;

  // ────────────────────────────────────────────────────────
  // 5. PAGINAÇÃO (client-side)
  // ────────────────────────────────────────────────────────
  const [pagina, setPagina] = useState(1);

  const professoresPaginados = useMemo(() => {
    const inicio = (pagina - 1) * POR_PAGINA;
    return professores.slice(inicio, inicio + POR_PAGINA);
  }, [professores, pagina]);

  const pagination = useMemo(() => {
    const lastPage = Math.max(1, Math.ceil(professores.length / POR_PAGINA));
    return {
      current_page: pagina,
      last_page: lastPage,
      total: professores.length,
      per_page: POR_PAGINA,
      from:
        professores.length > 0 ? (pagina - 1) * POR_PAGINA + 1 : 0,
      to: Math.min(pagina * POR_PAGINA, professores.length),
    };
  }, [professores.length, pagina]);

  return (
    <>
      <Head title={`Status: ${prazo.titulo}`} />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* ── Header ────────────────────────────────────── */}
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
                    <BreadcrumbLink asChild>
                      <Link
                        href={`/dashboard/diretor/prazos/${prazo.id}`}
                        className="line-clamp-2 text-sm font-semibold text-secondary"
                      >
                        {prazo.titulo}
                      </Link>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Status
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                {prazo.disciplina?.nome ?? 'Todas as disciplinas'}
                {prazo.classe?.nome && ` • ${prazo.classe.nome}`}
                {prazo.data_limite && ` • Limite: ${prazo.data_limite}`}
              </CardDescription>
            </div>
            <CardAction>
              <Button variant="outline" size="sm" asChild>
                <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
                  Voltar
                </Link>
              </Button>
            </CardAction>
          </CardHeader>
        </Card>

        {/* ── Stats ─────────────────────────────────────── */}
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-3">
          <Card className="gap-0 pb-0">
            <CardContent className="p-4">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Total
              </p>
              <p className="mt-1 text-xl font-bold">{total}</p>
            </CardContent>
          </Card>

          <Card className="gap-0 pb-0">
            <CardContent className="p-4">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Submetido
              </p>
              <p className="mt-1 text-xl font-bold">{cumpriram}</p>
            </CardContent>
          </Card>

          <Card className="gap-0 pb-0">
            <CardContent className="p-4">
              <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                Não submetido
              </p>
              <p className="mt-1 text-xl font-bold">{naoCumpriram}</p>
            </CardContent>
          </Card>
        </div>

        {/* ── Tabela ─────────────────────────────────────── */}
        <Card className="gap-0">
          <CardHeader className="flex flex-col gap-3 border-b md:flex-row md:items-center">
            <div className="min-w-0 md:flex-1">
              <CardTitle>Lista de Professores</CardTitle>
              <CardDescription>
                Uma linha por professor e turma. Aceitar justificativa concede
                prazo extra automaticamente.
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent className="p-0!">
            {professores.length === 0 ? (
              <EmptyState
                variant="table"
                title="Sem professores"
                description="Nenhum professor atribuído a este prazo."
              />
            ) : (
              <div className="w-full overflow-x-auto">
                <Table>
                  <TableHeader>
                    <TableRow className="bg-muted/72">
                      <TableHead className="min-w-48 px-4">Professor</TableHead>
                      <TableHead className="min-w-32 px-4">Turma</TableHead>
                      <TableHead className="min-w-36 px-4">Submetido</TableHead>
                      <TableHead className="min-w-32 px-4 text-center">
                        Data
                      </TableHead>
                      <TableHead className="min-w-56 px-4">
                        Justificativa
                      </TableHead>
                      <TableHead className="min-w-40 px-4 text-center">
                        Ações
                      </TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {professoresPaginados.map((prof) => {
                      const rowKey = `${prof.professor_id}-${prof.turma_id ?? 'sem-turma'}`;
                      const isSubmiting =
                        processing[`sub_${prof.submissao_id}`];
                      const isJustifying = prof.justificativa
                        ? processing[`just_${prof.justificativa.id}`]
                        : false;

                      return (
                        <TableRow key={rowKey}>
                          <TableCell className="px-4 font-medium">
                            <span className="truncate">
                              {prof.professor_nome}
                            </span>
                          </TableCell>

                          <TableCell className="px-4">
                            {prof.turma_nome && prof.turma_nome !== '—' ? (
                              <Badge variant="outline">
                                {prof.turma_nome}
                              </Badge>
                            ) : (
                              <span className="text-xs text-muted-foreground">
                                —
                              </span>
                            )}
                          </TableCell>

                          <TableCell className="px-4">
                            {prof.submeteu ? (
                              <div className="flex flex-wrap items-center gap-1">
                                <Badge variant="outline">Submetido</Badge>
                                {prof.estado && (
                                  <Badge variant="outline">
                                    {prof.estado_label}
                                  </Badge>
                                )}
                              </div>
                            ) : (
                              <Badge variant="outline">Não submetido</Badge>
                            )}
                          </TableCell>

                          <TableCell className="px-4 text-center text-xs text-muted-foreground whitespace-nowrap">
                            {prof.data_submissao || '—'}
                          </TableCell>

                          <TableCell className="px-4">
                            {prof.justificativa ? (
                              <div className="space-y-1">
                                <Badge variant="outline">
                                  {prof.justificativa.status_label}
                                </Badge>
                                <p className="line-clamp-2 text-xs text-muted-foreground">
                                  {prof.justificativa.motivo}
                                </p>
                                <p className="text-[10px] text-muted-foreground">
                                  {prof.justificativa.data_justificativa}
                                </p>
                              </div>
                            ) : (
                              <span className="text-xs text-muted-foreground">
                                —
                              </span>
                            )}
                          </TableCell>

                          <TableCell className="px-4 text-center">
                            {/* Submeteu pendente */}
                            {prof.submeteu && prof.estado === 'pendente' && (
                              <div className="flex justify-center gap-1">
                                <Button
                                  size="sm"
                                  onClick={() =>
                                    handleAvaliarSubmissao(
                                      prof.submissao_id,
                                      'aprovar',
                                    )
                                  }
                                  disabled={isSubmiting}
                                  className="hover:cursor-pointer"
                                >
                                  {isSubmiting ? <Spinner /> : 'Aprovar'}
                                </Button>
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() =>
                                    openRejectDialog(
                                      prof.submissao_id,
                                      prof.professor_nome,
                                      prof.turma_nome,
                                    )
                                  }
                                  disabled={isSubmiting}
                                  className="hover:cursor-pointer"
                                >
                                  Rejeitar
                                </Button>
                              </div>
                            )}

                            {prof.submeteu && prof.estado !== 'pendente' && (
                              <span className="text-xs text-muted-foreground">
                                Avaliado
                              </span>
                            )}

                            {/* Justificativa pendente */}
                            {!prof.submeteu &&
                              prof.justificativa &&
                              prof.justificativa.status === 'pendente' && (
                                <div className="flex justify-center gap-1">
                                  <Button
                                    size="sm"
                                    className="h-7 text-xs hover:cursor-pointer"
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
                                    {isJustifying ? <Spinner /> : 'Aceitar'}
                                  </Button>
                                  <Button
                                    size="sm"
                                    variant="outline"
                                    className="h-7 text-xs hover:cursor-pointer"
                                    onClick={() =>
                                      openRejectJustificativaDialog(
                                        prof.justificativa.id,
                                        prof.professor_nome,
                                        prof.turma_nome,
                                      )
                                    }
                                    disabled={isJustifying}
                                  >
                                    Recusar
                                  </Button>
                                </div>
                              )}

                            {!prof.submeteu &&
                              prof.justificativa &&
                              prof.justificativa.status !== 'pendente' && (
                                <span className="text-xs text-muted-foreground">
                                  {prof.justificativa.status_label}
                                </span>
                              )}

                            {!prof.submeteu && !prof.justificativa && (
                              <span className="text-xs text-muted-foreground">
                                Sem justificativa
                              </span>
                            )}
                          </TableCell>
                        </TableRow>
                      );
                    })}
                  </TableBody>
                </Table>
              </div>
            )}
          </CardContent>

          {pagination.total > 0 && (
            <TablePagination
              pagination={pagination}
              onPageChange={setPagina}
            />
          )}
        </Card>
      </div>

      {/* ── Dialog: Rejeitar submissão ─────────────────── */}
      <Dialog
        open={!!rejectDialog}
        onOpenChange={(open) => !open && closeRejectDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Rejeitar Submissão</DialogTitle>
            <DialogDescription>
              <strong>Professor:</strong> {rejectDialog?.professorNome}
              <br />
              <strong>Turma:</strong> {rejectDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>
          <Field>
            <FieldLabel>Motivo *</FieldLabel>
            <Textarea
              rows={3}
              value={motivoRejeicao}
              onChange={(e) => setMotivoRejeicao(e.target.value)}
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
              variant="outline"
              onClick={confirmReject}
              className="hover:cursor-pointer"
            >
              Rejeitar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Dialog: Recusar justificativa ──────────────── */}
      <Dialog
        open={!!justificativaDialog && !justificativaDialog.aceitar}
        onOpenChange={(open) => !open && closeJustificativaDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Recusar Justificativa</DialogTitle>
            <DialogDescription>
              <strong>{justificativaDialog?.professorNome}</strong> •{' '}
              {justificativaDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>
          <Field>
            <FieldLabel>
              Motivo{' '}
              <span className="text-xs font-normal text-muted-foreground">
                (opcional)
              </span>
            </FieldLabel>
            <Textarea
              rows={3}
              value={motivoRecusaJustificativa}
              onChange={(e) =>
                setMotivoRecusaJustificativa(e.target.value)
              }
            />
          </Field>
          <DialogFooter>
            <Button
              variant="outline"
              onClick={closeJustificativaDialog}
              className="hover:cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              variant="outline"
              onClick={confirmRejectJustificativa}
              className="hover:cursor-pointer"
            >
              Recusar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* ── Dialog: Aceitar justificativa ──────────────── */}
      <Dialog
        open={!!justificativaDialog && justificativaDialog.aceitar === true}
        onOpenChange={(open) => !open && closeJustificativaDialog()}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Aceitar Justificativa</DialogTitle>
            <DialogDescription>
              <strong>{justificativaDialog?.professorNome}</strong> •{' '}
              {justificativaDialog?.turmaNome}
            </DialogDescription>
          </DialogHeader>

          <div className="space-y-3">
            <div className="border bg-muted/50 p-3">
              <p className="text-sm text-muted-foreground">
                Ao aceitar, será concedido um prazo extra para o professor
                submeter a prova <strong>desta turma</strong>.
              </p>
            </div>

            <Field>
              <FieldLabel>Dias de prazo extra *</FieldLabel>
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
            </Field>
          </div>

          <DialogFooter>
            <Button
              variant="outline"
              onClick={closeJustificativaDialog}
              className="hover:cursor-pointer"
            >
              Cancelar
            </Button>
            <Button
              variant="default"
              onClick={confirmAceitar}
              className="hover:cursor-pointer"
            >
              Aceitar e conceder {diasPrazoExtra} dias
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}