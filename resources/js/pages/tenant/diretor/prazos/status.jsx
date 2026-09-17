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
  FileText,
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
  // Estados para processamento (loading)
  const [processing, setProcessing] = useState({});
  
  // Estado para o diálogo de rejeição de submissão
  const [rejectDialog, setRejectDialog] = useState(null); // { submissaoId, professorNome }
  const [motivoRejeicao, setMotivoRejeicao] = useState('');
  
  // Estado para o diálogo de recusa de justificativa
  const [justificativaDialog, setJustificativaDialog] = useState(null); // { id, professorNome }
  const [motivoRecusaJustificativa, setMotivoRecusaJustificativa] = useState('');

  // ============================================================
  // 1. AVALIAR SUBMISSÃO (aprovar/rejeitar)
  // ============================================================
  const handleAvaliarSubmissao = useCallback((submissaoId, acao, motivo = null) => {
    setProcessing((prev) => ({ ...prev, [submissaoId]: true }));

    const payload = { acao };
    if (motivo) payload.parecer = motivo;

    router.patch(`/dashboard/diretor/submissoes/${submissaoId}/avaliar`, payload, {
      onSuccess: () => {
        toast.success(`Submissão ${acao === 'aprovar' ? 'aprovada' : 'rejeitada'} com sucesso!`);
        router.reload();
      },
      onError: () => {
        toast.error('Erro ao avaliar. Tente novamente.');
      },
      onFinish: () => {
        setProcessing((prev) => ({ ...prev, [submissaoId]: false }));
      },
    });
  }, []);

  // ============================================================
  // 2. AVALIAR JUSTIFICATIVA (aceitar/recusar)
  // ============================================================
const handleAvaliarJustificativa = useCallback((justificativaId, status, motivo = null) => {
  setProcessing((prev) => ({ ...prev, [`just_${justificativaId}`]: true }));

  router.patch(
    `/dashboard/diretor/justificativas/${justificativaId}/avaliar`,
    { status, motivo },
    {
      onSuccess: () => {
        toast.success(`Justificativa ${status === 'aceita' ? 'aceita' : 'recusada'}!`);
    
      },
      onError: (errors) => {
        console.error('Erro:', errors);
        toast.error('Erro ao avaliar justificativa.');
      },
      onFinish: () => {
        setProcessing((prev) => ({ ...prev, [`just_${justificativaId}`]: false }));
        setJustificativaDialog(null);
      },
    }
  );
}, []);
  // ============================================================
  // 3. ABRIR/FECHAR DIÁLOGOS
  // ============================================================
  const openRejectDialog = (submissaoId, professorNome) => {
    setMotivoRejeicao('');
    setRejectDialog({ submissaoId, professorNome });
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

  const openRejectJustificativaDialog = (id, professorNome) => {
    setMotivoRecusaJustificativa('');
    setJustificativaDialog({ id, professorNome });
  };

  const closeJustificativaDialog = () => setJustificativaDialog(null);

  const confirmRejectJustificativa = () => {
    if (!justificativaDialog) return;
    const { id } = justificativaDialog;
    handleAvaliarJustificativa(
      id,
      'recusada',
      motivoRecusaJustificativa.trim() || null
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

      <div className="max-w-7xl mx-auto space-y-6 p-6">
        {/* Cabeçalho */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-2xl font-bold text-foreground">
            Status do Prazo: <span >{prazo.titulo}</span>
          </h1>
          <Button variant="outline" asChild>
            <Link href={`/dashboard/diretor/prazos/${prazo.id}`}>
              <ArrowLeft className="mr-1.5 size-4" />
              Voltar
            </Link>
          </Button>
        </div>

        {/* Cards de resumo */}
        <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
          <Card className="text-primary-foreground border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <Users className="size-8 mr-3" />
              <div>
                <CardTitle className="text-sm font-medium">Total de Professores</CardTitle>
                <p className="text-3xl font-bold">{total}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="text-white border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <CheckCircle className="size-8 mr-3" />
              <div>
                <CardTitle className="text-sm font-medium">Submeteram</CardTitle>
                <p className="text-3xl font-bold">{cumpriram}</p>
              </div>
            </CardContent>
          </Card>

          <Card className="text-destructive-foreground border-0 shadow-sm">
            <CardContent className="flex items-center p-4">
              <XCircle className="size-8 mr-3" />
              <div>
                <CardTitle className="text-sm font-medium">Não Submeteram</CardTitle>
                <p className="text-3xl font-bold">{naoCumpriram}</p>
              </div>
            </CardContent>
          </Card>
        </div>

        {/* Tabela de professores */}
        <Card className="shadow-sm border-border">
          <CardHeader>
            <CardTitle>Lista de Professores</CardTitle>
            <CardDescription>
              Status de submissão, justificativas e ações de avaliação.
            </CardDescription>
          </CardHeader>
          <CardContent className="p-0">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/50">
                  <TableHead className="px-4">Professor</TableHead>
                  <TableHead className="px-4">Status</TableHead>
                  <TableHead className="px-4 text-center">Versão</TableHead>
                  <TableHead className="px-4 text-center">Data da Submissão</TableHead>
                  <TableHead className="px-4">Justificativa</TableHead>
                  <TableHead className="px-4 text-center">Ações</TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {professores.map((prof) => (
                  <TableRow
                    key={prof.professor_id}
                    className={!prof.submeteu ? 'bg-yellow-50 dark:bg-yellow-950/20' : ''}
                  >
                    {/* Professor */}
                    <TableCell className="px-4">
                      <div className="flex items-center gap-2">
                        <div className="size-8 rounded-full bg-muted flex items-center justify-center">
                          <User className="size-4 text-muted-foreground" />
                        </div>
                        {prof.professor_nome}
                      </div>
                    </TableCell>

                    {/* Status de Submissão */}
                    <TableCell className="px-4">
                      {prof.submeteu ? (
                        <div className="flex flex-wrap items-center gap-1">
                          <Badge variant="default" className="bg-green-600 hover:bg-green-700">
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
                                  : ''
                              }
                            >
                              {prof.estado === 'aprovado' && <Check className="size-3 mr-1" />}
                              {prof.estado === 'rejeitado' && <X className="size-3 mr-1" />}
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
                    <TableCell className="px-4 text-center">{prof.versao || '-'}</TableCell>

                    {/* Data da Submissão */}
                    <TableCell className="px-4 text-center">{prof.data_submissao || '-'}</TableCell>

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
                            {prof.justificativa.status === 'pendente' && <AlertCircle className="size-3 mr-1" />}
                            {prof.justificativa.status === 'aceita' && <CheckCircle className="size-3 mr-1" />}
                            {prof.justificativa.status === 'recusada' && <XCircle className="size-3 mr-1" />}
                            {prof.justificativa.status_label}
                          </Badge>
                          <p className="text-sm text-muted-foreground">{prof.justificativa.motivo}</p>
                          <p className="text-xs text-muted-foreground">
                            {prof.justificativa.data_justificativa}
                          </p>
                        </div>
                      ) : (
                        <span className="text-sm text-muted-foreground">—</span>
                      )}
                    </TableCell>

                    {/* Ações */}
                    <TableCell className="px-4 text-center">
                      {/* 1. Professor submeteu -> avaliar submissão */}
                      {prof.submeteu && prof.estado === 'pendente' && (
                        <div className="flex justify-center gap-2">
                          <Button
                            variant="default"
                            size="sm"
                            className="bg-green-600 hover:bg-green-700"
                            onClick={() => handleAvaliarSubmissao(prof.submissao_id, 'aprovar')}
                            disabled={processing[prof.submissao_id]}
                          >
                            {processing[prof.submissao_id] ? (
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
                            onClick={() => openRejectDialog(prof.submissao_id, prof.professor_nome)}
                            disabled={processing[prof.submissao_id]}
                          >
                            {processing[prof.submissao_id] ? (
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

                      {/* 2. Professor submeteu e já foi avaliado */}
                      {prof.submeteu && prof.estado !== 'pendente' && (
                        <span className="text-sm text-muted-foreground flex items-center justify-center gap-1">
                          <CheckCircle className="size-4" />
                          Avaliado
                        </span>
                      )}

                      {/* 3. Professor não submeteu, mas tem justificativa pendente */}
                      {!prof.submeteu && prof.justificativa && prof.justificativa.status === 'pendente' && (
                        <div className="flex flex-col items-center gap-1">
                          <Badge variant="outline" className="border-yellow-500 text-yellow-700">
                            <AlertCircle className="size-3 mr-1" />
                            Aguardando análise
                          </Badge>
                          <div className="flex gap-1">
                            <Button
                              size="sm"
                              variant="default"
                              className="bg-green-600 hover:bg-green-700 text-xs h-7"
                              onClick={() => handleAvaliarJustificativa(prof.justificativa.id, 'aceita')}
                              disabled={processing[`just_${prof.justificativa.id}`]}
                            >
                              {processing[`just_${prof.justificativa.id}`] ? (
                                <Loader2 className="size-3 animate-spin" />
                              ) : (
                                <Check className="size-3 mr-1" />
                              )}
                              Aceitar
                            </Button>
                            <Button
                              size="sm"
                              variant="destructive"
                              className="text-xs h-7"
                              onClick={() => openRejectJustificativaDialog(prof.justificativa.id, prof.professor_nome)}
                              disabled={processing[`just_${prof.justificativa.id}`]}
                            >
                              {processing[`just_${prof.justificativa.id}`] ? (
                                <Loader2 className="size-3 animate-spin" />
                              ) : (
                                <X className="size-3 mr-1" />
                              )}
                              Recusar
                            </Button>
                          </div>
                        </div>
                      )}

                      {/* 4. Professor não submeteu, tem justificativa já avaliada */}
                      {!prof.submeteu && prof.justificativa && prof.justificativa.status !== 'pendente' && (
                        <span className="text-sm text-muted-foreground flex items-center justify-center gap-1">
                          {prof.justificativa.status === 'aceita' ? (
                            <CheckCircle className="size-4 text-green-600" />
                          ) : (
                            <XCircle className="size-4 text-red-600" />
                          )}
                          {prof.justificativa.status_label}
                        </span>
                      )}

                      {/* 5. Professor não submeteu e não tem justificativa */}
                      {!prof.submeteu && !prof.justificativa && (
                        <span className="text-sm text-muted-foreground">Sem justificativa</span>
                      )}
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </CardContent>
        </Card>
      </div>

      {/* ============================================================
          DIÁLOGO 1: Rejeitar Submissão
          ============================================================ */}
      <Dialog open={!!rejectDialog} onOpenChange={(open) => !open && closeRejectDialog()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Rejeitar Submissão</DialogTitle>
            <DialogDescription>
              <strong>Professor:</strong> {rejectDialog?.professorNome}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <label htmlFor="motivoRejeicao" className="text-sm font-medium">
              Motivo da rejeição
            </label>
            <Textarea
              id="motivoRejeicao"
              rows={3}
              placeholder="Descreva o motivo..."
              value={motivoRejeicao}
              onChange={(e) => setMotivoRejeicao(e.target.value)}
              required
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeRejectDialog}>Cancelar</Button>
            <Button variant="destructive" onClick={confirmReject}>Rejeitar</Button>
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
            <DialogTitle>Recusar Justificativa</DialogTitle>
            <DialogDescription>
              <strong>Professor:</strong> {justificativaDialog?.professorNome}
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <label htmlFor="motivoRecusaJustificativa" className="text-sm font-medium">
              Motivo da recusa <span className="text-muted-foreground">(opcional)</span>
            </label>
            <Textarea
              id="motivoRecusaJustificativa"
              rows={3}
              placeholder="Explique porque a justificativa foi recusada..."
              value={motivoRecusaJustificativa}
              onChange={(e) => setMotivoRecusaJustificativa(e.target.value)}
            />
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