import { router } from '@inertiajs/react';
import {
  CheckCircle,
  AlertCircle,
  FileText,
  Upload,
  ChevronDown,
  ChevronUp,
  XCircle,
} from 'lucide-react';
import { useState } from 'react';
import {
  submeter,
  aprovarComoTutor,
  solicitarCorrecaoComoTutor,
  aprovarComoCoordenacao,
  solicitarCorrecaoComoCoordenacao,
  download,
  visualizar,
  downloadCorrecao,
} from '@/actions/App/Http/Controllers/Tenant/TrabalhoPapController';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ModalDecisao } from '@/pages/tenant/pap/components/trabalho/modal-decisao';
import { VersaoCard } from '@/pages/tenant/pap/components/trabalho/versao-card';
import { getStatusTrabalho } from '@/pages/tenant/pap/config/status-trabalho';

// ── TabTrabalho ──────────────────────────────────────────────────────────────

export function TabTrabalho({ params, trabalho, can, errors = {} }) {
  const [ficheiro, setFicheiro] = useState(null);
  const [loadingSubmissao, setLoadingSubmissao] = useState(false);
  const [modalAberto, setModalAberto] = useState(false);
  const [action, setAction] = useState(null);
  const [loadingDecisao, setLoadingDecisao] = useState(false);

  const [verTodasVersoes, setVerTodasVersoes] = useState(false); // ← aqui

  const VERSOES_VISIVEIS = 3;
  const versoesOrdenadas = [...(trabalho?.versoes ?? [])].reverse(); // ← optional chaining
  const versoesMostradas = verTodasVersoes
    ? versoesOrdenadas
    : versoesOrdenadas.slice(0, VERSOES_VISIVEIS);

  if (!trabalho) {
    return (
      <Card>
        <CardContent className="flex flex-col items-center gap-3 py-12 text-center">
          <FileText className="size-10 text-muted-foreground/40" />
          <p className="text-sm text-muted-foreground">
            O trabalho ainda não está disponível.
          </p>
        </CardContent>
      </Card>
    );
  }

  const statusConfig = getStatusTrabalho(trabalho.status);
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
    if (!ficheiro) {
      return;
    }

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
    if (loadingDecisao) {
      return;
    }

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
      if (v != null) {
        formData.append(k, v);
      }
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
          {['correcao_tutor', 'correcao_coordenacao'].includes(
            trabalho.status,
          ) &&
            ultimaCorrecao && (
              <Alert variant="default">
                <AlertCircle className="w-4 h-4" />
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
              <CheckCircle className="w-4 h-4 text-green-600" />
              <AlertTitle className="text-green-700">
                Trabalho aprovado
              </AlertTitle>
              <AlertDescription className="text-sm text-green-600">
                Aprovado pelo {trabalho.aprovado_por} em{' '}
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
              <XCircle className="w-4 h-4" />
              <AlertTitle>Trabalho reprovado</AlertTitle>
              <AlertDescription className="text-sm">
                {ultimaCorrecao?.comentario ?? 'Sem comentário adicional.'}
              </AlertDescription>
            </Alert>
          )}

          {/* ── Área de submissão — aluno ──────────────────────────────── */}
          {podeSubmeter && (
            <div className="p-4 space-y-3 border">
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
                    {ficheiro.name} ({(ficheiro.size / 1024 / 1024).toFixed(2)}{' '}
                    MB)
                  </p>
                )}
                {errors.ficheiro && (
                  <p className="text-sm text-destructive">{errors.ficheiro}</p>
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
            (can?.aprovarTrabalhoComoTutor ||
              can?.solicitarCorrecaoComoTutor) && (
              <div className="p-4 space-y-3 border">
                <p className="text-sm text-muted-foreground">
                  Analisa o trabalho submetido e toma uma decisão.
                </p>
                <div className="flex flex-wrap justify-end gap-2">
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
            (can?.aprovarComoCoordenacao ||
              can?.solicitarCorrecaoComoCoordenacao) && (
              <div className="p-4 space-y-3 border">
                <p className="text-sm text-muted-foreground">
                  Analisa o trabalho e toma uma decisão.
                </p>
                <div className="flex flex-wrap justify-end gap-2">
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
      {trabalho.versoes?.length > 0 && (
        <Card>
          <CardHeader className="border-b">
            <CardTitle className="flex items-center gap-2 text-base">
              <FileText className="size-4" />
              Versões submetidas
            </CardTitle>
          </CardHeader>
          <CardContent className="pt-6 space-y-3">
            {versoesMostradas.map((versao) => (
              <VersaoCard
                key={versao.id}
                versao={versao}
                canDownload={can?.downloadVersao}
                visualizarUrl={visualizar.url({
                  ...params,
                  numeroVersao: versao.numero_versao,
                })}
                downloadUrl={download.url({
                  ...params,
                  numeroVersao: versao.numero_versao,
                })}
                getDownloadCorrecaoUrl={(feedbackId) =>
                  downloadCorrecao.url({ ...params, feedbackId })
                }
              />
            ))}

            {versoesOrdenadas.length > VERSOES_VISIVEIS && (
              <Button
                variant="ghost"
                className="w-full text-muted-foreground"
                onClick={() => setVerTodasVersoes(!verTodasVersoes)}
              >
                {verTodasVersoes
                  ? 'Ver menos'
                  : `Ver mais ${versoesOrdenadas.length - VERSOES_VISIVEIS} versão(ões) anteriores`}
                {verTodasVersoes ? (
                  <ChevronUp className="size-4" />
                ) : (
                  <ChevronDown className="size-4" />
                )}
              </Button>
            )}
          </CardContent>
        </Card>
      )}

      {/* ── Modal de decisão ─────────────────────────────────────────────── */}
      <ModalDecisao
        open={modalAberto}
        onClose={fecharModal}
        action={action}
        onConfirmar={confirmarDecisao}
        loading={loadingDecisao}
        minimumCommentLength={10}
        validateOnSubmit
      />
    </div>
  );
}
