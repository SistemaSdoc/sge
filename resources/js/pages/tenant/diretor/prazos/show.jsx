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
} from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Show({ prazo, submissoes }) {
  const [loading, setLoading] = useState(false);
  const [prorrogarDialog, setProrrogarDialog] = useState(false);
  const [novaData, setNovaData] = useState('');
  const [rejectDialog, setRejectDialog] = useState(null); // { submissaoId, motivo }

  // Fechar prazo
  const handleFecharPrazo = useCallback(() => {
    if (!confirm('Tem certeza que deseja encerrar este prazo? Esta ação não pode ser desfeita.')) return;
    setLoading(true);
    router.post(`/dashboard/diretor/prazos/${prazo.id}/fechar`, {}, {
      onSuccess: () => {
        toast.success('Prazo encerrado com sucesso!');
        router.reload();
      },
      onError: () => toast.error('Erro ao encerrar prazo'),
      onFinish: () => setLoading(false),
    });
  }, [prazo.id]);

  // Prorrogar prazo
  const handleProrrogar = useCallback(() => {
    if (!novaData) {
      toast.error('Informe a nova data limite.');
      return;
    }
    setLoading(true);
    router.post(`/dashboard/diretor/prazos/${prazo.id}/prorrogar`, {
      nova_data_limite: novaData,
    }, {
      onSuccess: () => {
        toast.success('Prazo prorrogado com sucesso!');
        setProrrogarDialog(false);
        router.reload();
      },
      onError: () => toast.error('Erro ao prorrogar prazo'),
      onFinish: () => setLoading(false),
    });
  }, [prazo.id, novaData]);

  // Avaliar submissão (aprovar/rejeitar)
  const handleAvaliar = useCallback((submissaoId, acao, motivo = null) => {
    setLoading(true);
    const payload = { acao };
    if (motivo) payload.parecer = motivo;

    router.patch(`/dashboard/diretor/submissoes/${submissaoId}/avaliar`, payload, {
      onSuccess: () => {
        toast.success(`Submissão ${acao === 'aprovar' ? 'aprovada' : 'rejeitada'} com sucesso!`);
        router.reload();
      },
      onError: () => toast.error('Erro ao avaliar submissão'),
      onFinish: () => setLoading(false),
    });
  }, []);

  // Abrir diálogo de rejeição
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

      <div className="max-w-7xl mx-auto px-4 py-6">
        {/* Breadcrumb */}
        <nav className="flex items-center gap-2 text-sm text-muted-foreground mb-4">
          <Link href="/dashboard" className="hover:text-foreground transition-colors">
            Dashboard
          </Link>
          <span>/</span>
          <Link href="/dashboard/diretor/prazos" className="hover:text-foreground transition-colors">
            Prazos
          </Link>
          <span>/</span>
          <span className="text-foreground font-medium">{prazo.titulo}</span>
        </nav>

        {/* Cabeçalho com ações */}
        <div className="flex flex-wrap items-center justify-between gap-4 mb-6">
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
        <Card className="shadow-sm border-border mb-6">
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>Detalhes do Prazo</CardTitle>
            <Badge variant="outline" className={prazo.badge_class}>
              {prazo.status_label}
            </Badge>
          </CardHeader>
          <CardContent className="grid grid-cols-2 gap-4 md:grid-cols-4 pt-4">
            <div className="flex items-center gap-2 text-sm">
              <Tag className="size-4 text-muted-foreground" />
              <span><strong>Tipo:</strong> {prazo.tipo_prova}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <BookOpen className="size-4 text-muted-foreground" />
              <span><strong>Disciplina:</strong> {prazo.disciplina?.nome || 'Todas'}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Users className="size-4 text-muted-foreground" />
              <span><strong>Classe:</strong> {prazo.classe?.nome || 'Todas'}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="size-4 text-muted-foreground" />
              <span><strong>Início:</strong> {prazo.data_inicio}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Clock className="size-4 text-muted-foreground" />
              <span><strong>Limite:</strong> {prazo.data_limite}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Calendar className="size-4 text-muted-foreground" />
              <span><strong>Ano Lectivo:</strong> {prazo.ano_letivo}</span>
            </div>
            <div className="flex items-center gap-2 text-sm">
              <Clock className="size-4 text-muted-foreground" />
              <span><strong>Período:</strong> {prazo.periodo}</span>
            </div>
            <div className="flex items-center gap-2 text-sm col-span-2 md:col-span-4">
              <MessageSquare className="size-4 text-muted-foreground" />
              <span><strong>Observações:</strong> {prazo.observacoes || 'Nenhuma'}</span>
            </div>
          </CardContent>
        </Card>

       
          <div className="flex flex-wrap gap-2 mb-6">
         {/* Ações do prazo (apenas se aberto) */}
        {prazo.status === 'aberto' && (
            <Button variant="destructive" onClick={handleFecharPrazo} disabled={loading}>
              {loading ? <Loader2 className="size-4 animate-spin mr-1.5" /> : <Lock className="mr-1.5 size-4" />}
              Encerrar
            </Button>
        )}
            <Button variant="default" onClick={() => setProrrogarDialog(true)} disabled={loading}>
              {loading ? <Loader2 className="size-4 animate-spin mr-1.5" /> : <ClockArrowUp className="mr-1.5 size-4" />}
              Prorrogar
            </Button>
          </div>

        {/* Lista de Submissões */}
        <div className="flex items-center gap-2 mb-4">
          <FileText className="size-5" />
          <h2 className="text-lg font-semibold">Submissões</h2>
          <Badge variant="secondary">{submissoes.length}</Badge>
        </div>

        {submissoes.length === 0 ? (
          <div className="flex items-center gap-2 text-muted-foreground border rounded-lg p-4 bg-muted/30">
            <Info className="size-5" />
            <span>Nenhuma submissão ainda.</span>
          </div>
        ) : (
          <div className="space-y-4">
            {submissoes.map((sub) => (
              <Card key={sub.id} className="shadow-sm border-border">
                <CardContent className="p-4">
                  <div className="flex flex-wrap items-center gap-4">
                    {/* Professor */}
                    <div className="flex items-center gap-3 min-w-[180px]">
                      <div className="size-10 rounded-full bg-muted flex items-center justify-center">
                        <User className="size-5 text-muted-foreground" />
                      </div>
                      <div>
                        <p className="font-medium">{sub.professor?.nome || 'Professor'}</p>
                        <p className="text-xs text-muted-foreground">Versão {sub.versao}</p>
                      </div>
                    </div>

                    {/* Estado */}
                    <Badge variant="outline" className={sub.badge_class}>
                      {sub.estado_label}
                    </Badge>

                    {/* Links para arquivos */}
                    <div className="flex gap-1">
                      <Button variant="outline" size="sm" asChild>
                        <a href={sub.url_prova} target="_blank" rel="noopener noreferrer">
                          <File className="mr-1 size-4" />
                          Prova
                        </a>
                      </Button>
                      <Button variant="outline" size="sm" asChild>
                        <a href={sub.url_chave} target="_blank" rel="noopener noreferrer">
                          <FileIcon className="mr-1 size-4" />
                          Chave
                        </a>
                      </Button>
                    </div>

                    {/* Ações de avaliação */}
                    <div className="ml-auto flex items-center gap-2">
                      {sub.estado === 'pendente' && (
                        <>
                          <Button
                            variant="default"
                            size="sm"
                            className="bg-green-600 hover:bg-green-700"
                            onClick={() => handleAvaliar(sub.id, 'aprovar')}
                            disabled={loading}
                          >
                            {loading ? <Loader2 className="size-4 animate-spin" /> : <Check className="mr-1 size-4" />}
                            Aprovar
                          </Button>
                          <Button
                            variant="destructive"
                            size="sm"
                            onClick={() => openRejectDialog(sub.id)}
                            disabled={loading}
                          >
                            {loading ? <Loader2 className="size-4 animate-spin" /> : <X className="mr-1 size-4" />}
                            Rejeitar
                          </Button>
                        </>
                      )}
                      {sub.estado !== 'pendente' && (
                        <span className="text-sm text-muted-foreground flex items-center gap-1">
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

                  {/* Parecer e comentário (condicionais) */}
                  {sub.parecer && (
                    <div className="mt-3 p-2 bg-muted/50 rounded text-sm">
                      <strong>Remitente:</strong> {sub.parecer}
                    </div>
                  )}
                  {sub.comentario && (
                    <div className="mt-1 text-sm text-muted-foreground">
                      <strong>Comentário do professor:</strong> {sub.comentario}
                    </div>
                  )}
                </CardContent>
              </Card>
            ))}
          </div>
        )}
      </div>

      {/* Diálogo de Prorrogação */}
      <Dialog open={prorrogarDialog} onOpenChange={setProrrogarDialog}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Prorrogar Prazo</DialogTitle>
            <DialogDescription>
              Informe a nova data limite para o prazo.
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
            <Button variant="outline" onClick={() => setProrrogarDialog(false)}>Cancelar</Button>
            <Button variant="default" onClick={handleProrrogar} disabled={loading}>
              {loading ? <Loader2 className="size-4 animate-spin mr-1.5" /> : null}
              Prorrogar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>

      {/* Diálogo de Rejeição */}
      <Dialog open={!!rejectDialog} onOpenChange={(open) => !open && closeRejectDialog()}>
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Rejeitar Submissão</DialogTitle>
            <DialogDescription>
              Informe o motivo da rejeição.
            </DialogDescription>
          </DialogHeader>
          <div className="space-y-2 py-2">
            <Label htmlFor="motivo">Motivo</Label>
            <Input
              id="motivo"
              type="text"
              placeholder="Descreva o motivo..."
              value={rejectDialog?.motivo || ''}
              onChange={(e) => setRejectDialog(prev => ({ ...prev, motivo: e.target.value }))}
              required
            />
          </div>
          <DialogFooter>
            <Button variant="outline" onClick={closeRejectDialog}>Cancelar</Button>
            <Button variant="destructive" onClick={confirmReject}>
              Rejeitar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}