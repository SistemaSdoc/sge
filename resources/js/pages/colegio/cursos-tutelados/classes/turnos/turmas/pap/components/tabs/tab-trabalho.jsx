import { useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Input } from '@/components/ui/input';
import {
  CheckCircle,
  AlertCircle,
  Clock,
  FileText,
  Upload,
  Download,
  Eye,
  ChevronDown,
  ChevronUp,
  XCircle,
} from 'lucide-react';
import {
  submeter,
  aprovarComoTutor,
  solicitarCorrecaoComoTutor,
  aprovarComoCoordenacao,
  solicitarCorrecaoComoCoordenacao,
  download,
  visualizar,
  downloadCorrecao,
} from '@/actions/App/Http/Controllers/Colegios/TrabalhoPapController';

// ── Configuração de status ───────────────────────────────────────────────────

const STATUS_CONFIG = {
  pendente_entrega: {
    label: 'Aguarda entrega',
    icon: Clock,
    badge: 'bg-muted text-muted-foreground border-transparent',
  },
  em_analise_tutor: {
    label: 'Em análise — Tutor',
    icon: Clock,
    badge: 'bg-blue-50 text-blue-700 border-blue-200',
  },
  correcao_tutor: {
    label: 'Correção solicitada — Tutor',
    icon: AlertCircle,
    badge: 'bg-amber-50 text-amber-700 border-amber-200',
  },
  em_analise_coordenacao: {
    label: 'Em análise — Coordenação',
    icon: Clock,
    badge: 'bg-purple-50 text-purple-700 border-purple-200',
  },
  correcao_coordenacao: {
    label: 'Correção solicitada — Coordenação',
    icon: AlertCircle,
    badge: 'bg-orange-50 text-orange-700 border-orange-200',
  },
  aprovado: {
    label: 'Aprovado',
    icon: CheckCircle,
    badge: 'bg-green-50 text-green-700 border-green-200',
  },
  reprovado: {
    label: 'Reprovado',
    icon: XCircle,
    badge: 'bg-red-50 text-red-700 border-red-200',
  },
};

const FEEDBACK_CONFIG = {
  correcao_tutor: {
    label: 'Correção solicitada pelo tutor',
    color: 'text-amber-700',
    bg: 'bg-amber-50 border-amber-200',
    icon: AlertCircle,
  },
  aprovacao_tutor: {
    label: 'Aprovado pelo tutor — enviado para coordenação',
    color: 'text-blue-700',
    bg: 'bg-blue-50 border-blue-200',
    icon: CheckCircle,
  },
  correcao_coordenacao: {
    label: 'Correção solicitada pela coordenação',
    color: 'text-orange-700',
    bg: 'bg-orange-50 border-orange-200',
    icon: AlertCircle,
  },
  aprovacao_coordenacao: {
    label: 'Aprovado pela coordenação',
    color: 'text-green-700',
    bg: 'bg-green-50 border-green-200',
    icon: CheckCircle,
  },
  reprovacao_coordenacao: {
    label: 'Reprovado pela coordenação',
    color: 'text-red-700',
    bg: 'bg-red-50 border-red-200',
    icon: XCircle,
  },
};

const getStatus = (status) =>
  STATUS_CONFIG[status] ?? STATUS_CONFIG.pendente_entrega;

// ── VersaoCard ───────────────────────────────────────────────────────────────

function VersaoCard({ versao, canDownload, downloadUrl, visualizarUrl, params }) {
  const [expandido, setExpandido] = useState(false);

  return (
    <div className="border bg-card p-4 space-y-3">
      <div className="flex items-center justify-between flex-wrap gap-2">
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 items-center justify-center rounded-full bg-primary/10 shrink-0">
            <span className="text-xs font-semibold text-primary">
              v{versao.numero_versao}
            </span>
          </div>
          <div>
            <p className="text-sm font-medium">{versao.nome_original}</p>
            <p className="text-xs text-muted-foreground">
              {versao.submetido_por} ·{' '}
              {new Date(versao.created_at).toLocaleString('pt-PT', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
              })}
            </p>
          </div>
        </div>

        <div className="flex items-center gap-2 flex-wrap">
          {/* Visualizar — abre numa nova aba */}
          {canDownload && (
            <Button variant="outline" size="sm" asChild>
              <a href={visualizarUrl} target="_blank" rel="noopener noreferrer">
                <Eye className="size-4" />
                Visualizar
              </a>
            </Button>
          )}

          {/* Download */}
          {canDownload && (
            <Button variant="outline" size="sm" asChild>
              <a href={downloadUrl} target="_blank" rel="noopener noreferrer">
                <Download className="size-4" />
                Download
              </a>
            </Button>
          )}

          {versao.feedbacks?.length > 0 && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setExpandido(!expandido)}
            >
              {expandido ? (
                <ChevronUp className="size-4" />
              ) : (
                <ChevronDown className="size-4" />
              )}
              {versao.feedbacks.length} comentário(s)
            </Button>
          )}
        </div>
      </div>

      {expandido && versao.feedbacks?.length > 0 && (
        <div className="space-y-2 pt-2 border-t">
          {versao.feedbacks.map((feedback) => {
            const config = FEEDBACK_CONFIG[feedback.tipo] ?? {};
            const Icon = config.icon ?? AlertCircle;

            return (
              <div
                key={feedback.id}
                className={`border p-3 ${config.bg}`}
              >
                <div className="flex items-center gap-2 mb-1 flex-wrap">
                  <Icon className={`size-4 ${config.color} shrink-0`} />
                  <span className={`text-xs font-medium ${config.color}`}>
                    {config.label}
                  </span>
                  <span className="ml-auto text-xs text-muted-foreground">
                    {feedback.utilizador} ·{' '}
                    {new Date(feedback.created_at).toLocaleString('pt-PT', {
                      day: '2-digit',
                      month: 'short',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </span>
                </div>
                {feedback.comentario && (
                  <p className="text-sm text-foreground/80 mt-1">
                    {feedback.comentario}
                  </p>
                )}
                {/* ← adiciona aqui */}
                {feedback.tem_ficheiro_correcao && (
                  <a
                    href={downloadCorrecao.url({ ...params, feedbackId: feedback.id })}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1 text-xs text-primary underline-offset-2 hover:underline mt-1"
                  >
                    <Download className="size-3" />
                    {feedback.nome_original_correcao ?? 'Descarregar PDF com correções'}
                  </a>
                )
                }
              </div>
            );
          })}
        </div>
      )
      }
    </div >
  );
}

// ── Modal de decisão ─────────────────────────────────────────────────────────

function ModalDecisao({ open, onClose, action, onConfirmar, loading }) {
  const [comentario, setComentario] = useState('');
  const [ficheiro, setFicheiro] = useState(null);

  const MODAL_CONFIG = {
    aprovarTutor: {
      titulo: 'Enviar para a coordenação',
      descricao: 'O trabalho será enviado para análise da coordenação do curso.',
      obrigatorio: false,
      confirmLabel: 'Enviar para coordenação',
      confirmVariant: 'default',
    },
    correcaoTutor: {
      titulo: 'Solicitar correção ao aluno',
      descricao: 'Descreve as correções necessárias. O aluno receberá este feedback e deverá submeter uma nova versão.',
      obrigatorio: true,
      confirmLabel: 'Solicitar correção',
      confirmVariant: 'outline',
    },
    aprovarCoordenacao: {
      titulo: 'Aprovar trabalho',
      descricao: 'O trabalho será aprovado definitivamente.',
      obrigatorio: false,
      confirmLabel: 'Aprovar',
      confirmVariant: 'default',
    },
    correcaoCoordenacao: {
      titulo: 'Solicitar correção ao aluno',
      descricao: 'O trabalho voltará ao aluno. Após correcção, passará novamente pelo tutor antes de chegar à coordenação.',
      obrigatorio: true,
      confirmLabel: 'Solicitar correção',
      confirmVariant: 'outline',
    },
    reprovarCoordenacao: {
      titulo: 'Reprovar trabalho',
      descricao: 'O trabalho será reprovado definitivamente. Esta acção não pode ser revertida.',
      obrigatorio: true,
      confirmLabel: 'Reprovar',
      confirmVariant: 'destructive',
    },
  };

  const config = MODAL_CONFIG[action] ?? {};

  const pedeFicheiro = ['correcaoTutor', 'correcaoCoordenacao'].includes(action);

  const handleClose = () => {
    if (loading) return;
    setComentario('');
    setFicheiro(null);
    onClose();
  };

  // Reset comentário quando o modal abre com nova action
  const handleOpenChange = (open) => {
    if (!open) handleClose();
  };

  return (
    <Dialog open={open} onOpenChange={(o) => { if (!o) handleClose(); }}>
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{config.titulo}</DialogTitle>
        </DialogHeader>

        <div className="space-y-4 py-2">
          <p className="text-sm text-muted-foreground">{config.descricao}</p>

          {/* Upload do PDF corrigido — só nas correções */}
          {pedeFicheiro && (
            <div className="space-y-1.5">
              <Label htmlFor="ficheiro-correcao">
                PDF com correções <span className="text-muted-foreground">(opcional)</span>
              </Label>
              <Input
                id="ficheiro-correcao"
                type="file"
                accept=".pdf"
                onChange={(e) => setFicheiro(e.target.files?.[0] ?? null)}
              />
              {ficheiro && (
                <p className="text-xs text-muted-foreground">
                  {ficheiro.name} ({(ficheiro.size / 1024 / 1024).toFixed(2)} MB)
                </p>
              )}
            </div>
          )}

          <div className="space-y-1.5">
            <Label htmlFor="comentario-trabalho">
              Comentário{config.obrigatorio ? ' *' : ' (opcional)'}
            </Label>
            <Textarea
              id="comentario-trabalho"
              rows={4}
              value={comentario}
              onChange={(e) => setComentario(e.target.value)}
              placeholder={
                config.obrigatorio
                  ? 'Descreve as correções necessárias...'
                  : 'Adiciona um comentário opcional...'
              }
            />
          </div>
        </div>

        <DialogFooter>
          <Button variant="outline" onClick={handleClose} disabled={loading}>
            Cancelar
          </Button>
          <Button
            variant={config.confirmVariant ?? 'default'}
            onClick={() => onConfirmar(comentario, ficheiro)}   // <-- passa ficheiro
            disabled={loading || (config.obrigatorio && !comentario.trim())}
          >
            {loading ? 'A processar...' : config.confirmLabel}
          </Button>
        </DialogFooter>
      </DialogContent>
    </Dialog>
  );
}
// ── TabTrabalho ──────────────────────────────────────────────────────────────

export function TabTrabalho({ params, grupoPap, trabalho, can }) {
  const [ficheiro, setFicheiro] = useState(null);
  const [loadingSubmissao, setLoadingSubmissao] = useState(false);
  const [modalAberto, setModalAberto] = useState(false);
  const [action, setAction] = useState(null);
  const [loadingDecisao, setLoadingDecisao] = useState(false);

  if (!trabalho) {
    return (
      <Card>
        <CardContent className="py-12 flex flex-col items-center gap-3 text-center">
          <FileText className="size-10 text-muted-foreground/40" />
          <p className="text-sm text-muted-foreground">
            O trabalho ainda não está disponível.
          </p>
        </CardContent>
      </Card>
    );
  }

  const statusConfig = getStatus(trabalho.status);
  const StatusIcon = statusConfig.icon;

  // Última correcção pendente — para mostrar no banner
  const ultimaCorrecao = trabalho.versoes
    ?.flatMap((v) => v.feedbacks ?? [])
    .filter((f) => ['correcao_tutor', 'correcao_coordenacao'].includes(f.tipo))
    .at(-1);

  // O aluno pode submeter nestes estados
  const podeSubmeter =
    can?.submeter &&
    ['pendente_entrega', 'correcao_tutor', 'correcao_coordenacao'].includes(
      trabalho.status,
    );

  const handleSubmeter = () => {
    if (!ficheiro) return;
    setLoadingSubmissao(true);

    const formData = new FormData();
    formData.append('ficheiro', ficheiro);

    router.post(submeter.url(params), formData, {
      forceFormData: true,
      onSuccess: () => {
        setLoadingSubmissao(false);
        setFicheiro(null);
        router.reload();
      },
      onError: () => setLoadingSubmissao(false),
    });
  };

  const abrirModal = (tipo) => {
    setAction(tipo);
    setModalAberto(true);
  };

  const fecharModal = () => {
    if (loadingDecisao) return;
    setModalAberto(false);
    setAction(null);
  };

  const confirmarDecisao = (comentario, ficheiro) => {
    setLoadingDecisao(true);

    const rotaMap = {
      aprovarTutor: aprovarComoTutor.url(params),
      correcaoTutor: solicitarCorrecaoComoTutor.url(params),
      aprovarCoordenacao: aprovarComoCoordenacao.url(params),
      correcaoCoordenacao: solicitarCorrecaoComoCoordenacao.url(params),
      // reprovar usa a mesma rota de solicitarCorrecaoComoCoordenacao
      // com um campo extra, ou podes criar uma rota dedicada
      reprovarCoordenacao: solicitarCorrecaoComoCoordenacao.url(params),
    };

    const formData = new FormData();

    const payload = {
      aprovarTutor: { comentario: comentario || '' },
      correcaoTutor: { comentario },
      aprovarCoordenacao: { comentario: comentario || '' },
      correcaoCoordenacao: { comentario },
      reprovarCoordenacao: { comentario },
    }[action];

    Object.entries(payload).forEach(([k, v]) => {
      if (v != null) formData.append(k, v);
    });

    // Anexar ficheiro se existir
    if (ficheiro) {
      formData.append('ficheiro', ficheiro);
    }

    router.post(rotaMap[action], formData, {
      forceFormData: true,
      preserveScroll: true,
      onSuccess: () => {
        setLoadingDecisao(false);
        fecharModal();
        router.reload();
      },
      onError: () => setLoadingDecisao(false),
    });
  };

  return (
    <div className="w-full space-y-6">

      {/* ── Card de estado atual ─────────────────────────────────────────── */}
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div className="flex items-center justify-between">
            <CardTitle>Trabalho PAP</CardTitle>
            <Badge
              variant="outline"
              className={`gap-1.5 text-xs font-normal ${statusConfig.badge}`}
            >
              <StatusIcon className="size-3.5" />
              {statusConfig.label}
            </Badge>
          </div>
        </CardHeader>

        <CardContent className="pt-6 space-y-4">

          {/* Banner — correcção pendente */}
          {['correcao_tutor', 'correcao_coordenacao'].includes(trabalho.status) &&
            ultimaCorrecao && (
              <Alert variant="default">
                <AlertCircle className="h-4 w-4" />
                <AlertTitle>
                  {trabalho.status === 'correcao_tutor'
                    ? 'O professor tutor solicitou correções'
                    : 'A coordenação solicitou correções'}
                </AlertTitle>
                <AlertDescription className="mt-1 text-sm">
                  {ultimaCorrecao.comentario ?? 'Sem comentário adicional.'}
                </AlertDescription>
                {/* ← adiciona aqui */}
                {/*{ultimaCorrecao.tem_ficheiro_correcao && params && (

                  <a
                    href={downloadCorrecao.url({ ...params, feedbackId: ultimaCorrecao.id })}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="inline-flex items-center gap-1.5 text-sm text-primary underline-offset-2 hover:underline mt-2"
                  >
                    <Download className="size-4" />
                    {ultimaCorrecao.nome_original_correcao ?? 'Descarregar PDF com correções'}
                  </a>
                )}*/}
              </Alert>
            )}

          {/* Banner — aprovado */}
          {trabalho.status === 'aprovado' && (
            <Alert>
              <CheckCircle className="h-4 w-4 text-green-600" />
              <AlertTitle className="text-green-700">Trabalho aprovado</AlertTitle>
              <AlertDescription className="text-sm text-green-600">
                Aprovado por {trabalho.aprovado_por} em{' '}
                {new Date(trabalho.data_aprovacao).toLocaleString('pt-PT', {
                  day: '2-digit',
                  month: 'long',
                  year: 'numeric',
                })}
              </AlertDescription>
            </Alert>
          )}

          {/* Banner — reprovado */}
          {trabalho.status === 'reprovado' && (
            <Alert variant="destructive">
              <XCircle className="h-4 w-4" />
              <AlertTitle>Trabalho reprovado</AlertTitle>
              <AlertDescription className="text-sm">
                {ultimaCorrecao?.comentario ?? 'Sem comentário adicional.'}
              </AlertDescription>
            </Alert>
          )}

          {/* ── Área de submissão — aluno ──────────────────────────────── */}
          {podeSubmeter && (
            <div className="space-y-3 rounded-lg border p-4">
              <p className="text-sm font-medium">
                {trabalho.status === 'pendente_entrega'
                  ? 'Submete o trabalho em formato PDF'
                  : 'Submete a versão corrigida em formato PDF'}
              </p>
              <div className="space-y-1.5">
                <Label htmlFor="ficheiro-trabalho">Ficheiro PDF</Label>
                <Input
                  id="ficheiro-trabalho"
                  type="file"
                  accept=".pdf"
                  onChange={(e) => setFicheiro(e.target.files?.[0] ?? null)}
                />
                {ficheiro && (
                  <p className="text-xs text-muted-foreground">
                    {ficheiro.name} ({(ficheiro.size / 1024 / 1024).toFixed(2)} MB)
                  </p>
                )}
              </div>
              <div className="flex justify-end">
                <Button
                  onClick={handleSubmeter}
                  disabled={!ficheiro || loadingSubmissao}
                >
                  <Upload className="size-4" />
                  {loadingSubmissao ? 'A submeter...' : 'Submeter'}
                </Button>
              </div>
            </div>
          )}

          {/* ── Área de decisão — Tutor ────────────────────────────────── */}
          {trabalho.status === 'em_analise_tutor' &&
            (can?.aprovarTrabalhoComoTutor || can?.solicitarCorrecaoComoTutor) && (
              <div className="rounded-lg border p-4 space-y-3">
                <p className="text-sm text-muted-foreground">
                  Analisa o trabalho submetido e toma uma decisão.
                </p>
                <div className="flex justify-end gap-2 flex-wrap">
                  {can?.solicitarCorrecaoComoTutor && (
                    <Button
                      variant="outline"
                      onClick={() => abrirModal('correcaoTutor')}
                    >
                      <AlertCircle className="size-4" />
                      Solicitar correção
                    </Button>
                  )}
                  {can?.aprovarTrabalhoComoTutor && (
                    <Button onClick={() => abrirModal('aprovarTutor')}>
                      <CheckCircle className="size-4" />
                      Enviar para coordenação
                    </Button>
                  )}
                </div>
              </div>
            )}

          {/* ── Área de decisão — Coordenação ─────────────────────────── */}
          {trabalho.status === 'em_analise_coordenacao' &&
            (can?.aprovarComoCoordenacao || can?.solicitarCorrecaoComoCoordenacao) && (
              <div className="rounded-lg border p-4 space-y-3">
                <p className="text-sm text-muted-foreground">
                  Analisa o trabalho e toma uma decisão.
                </p>
                <div className="flex justify-end gap-2 flex-wrap">
                  {can?.solicitarCorrecaoComoCoordenacao && (
                    <Button
                      variant="outline"
                      onClick={() => abrirModal('correcaoCoordenacao')}
                    >
                      <AlertCircle className="size-4" />
                      Solicitar correção
                    </Button>
                  )}
                  {can?.solicitarCorrecaoComoCoordenacao && (
                    <Button
                      variant="destructive"
                      onClick={() => abrirModal('reprovarCoordenacao')}
                    >
                      <XCircle className="size-4" />
                      Reprovar
                    </Button>
                  )}
                  {can?.aprovarComoCoordenacao && (
                    <Button onClick={() => abrirModal('aprovarCoordenacao')}>
                      <CheckCircle className="size-4" />
                      Aprovar trabalho
                    </Button>
                  )}
                </div>
              </div>
            )}

          {/* ── Estado neutro — aguarda outra parte ───────────────────── */}
          {!podeSubmeter &&
            !['aprovado', 'reprovado'].includes(trabalho.status) &&
            trabalho.status !== 'em_analise_tutor' &&
            trabalho.status !== 'em_analise_coordenacao' && (
              <p className="text-sm text-muted-foreground">
                Aguarda a submissão do trabalho pelos alunos.
              </p>
            )}

          {/* Aguarda sem acção (tutor/coordenação a ver mas sem permissão) */}
          {trabalho.status === 'em_analise_tutor' &&
            !can?.aprovarTrabalhoComoTutor &&
            !can?.solicitarCorrecaoComoTutor && (
              <p className="text-sm text-muted-foreground">
                O trabalho está em análise pelo professor tutor.
              </p>
            )}

          {trabalho.status === 'em_analise_coordenacao' &&
            !can?.aprovarComoCoordenacao &&
            !can?.solicitarCorrecaoComoCoordenacao && (
              <p className="text-sm text-muted-foreground">
                O trabalho está em análise pela coordenação.
              </p>
            )}

        </CardContent>
      </Card>

      {/* ── Histórico de versões ─────────────────────────────────────────── */}
      {
        trabalho.versoes?.length > 0 && (
          <Card>
            <CardHeader className="border-b">
              <CardTitle className="text-base flex items-center gap-2">
                <FileText className="size-4" />
                Versões submetidas
              </CardTitle>
            </CardHeader>
            <CardContent className="pt-6 space-y-3">
              {[...trabalho.versoes].reverse().map((versao) => (
                <VersaoCard
                  key={versao.id}
                  versao={versao}
                  params={params}
                  canDownload={can?.downloadVersao}
                  visualizarUrl={visualizar.url({ ...params, numeroVersao: versao.numero_versao })}
                  downloadUrl={download.url({ ...params, numeroVersao: versao.numero_versao })}
                />
              ))}
            </CardContent>
          </Card>
        )
      }

      {/* ── Modal de decisão ─────────────────────────────────────────────── */}
      <ModalDecisao
        open={modalAberto}
        onClose={fecharModal}
        action={action}
        onConfirmar={confirmarDecisao}
        loading={loadingDecisao}
      />
    </div >
  );
}