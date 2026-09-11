import { usePage, router } from '@inertiajs/react';
import { useState } from 'react';
import AlertError from '@/components/alert-error';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import {
  enviarParaTutela,
  processarDecisao,       
  emitir,
  marcarComoLevantado,
  marcarComoPagoAction,    
} from '@/actions/App/Http/Controllers/SolicitacaoDocumentoController';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';
import RequestHistoryDrawer from '@/components/RequestHistoryDrawer';

export default function ColegioSolicitacoesDocumentosPage() {
  const { solicitacoes = [], auth = {}, ver = null } = usePage().props;
  const [errors, setErrors] = useState([]);

  const handleEnviar = (solicitacaoId) => {
    router.post(
      enviarParaTutela(solicitacaoId).url,
      {},
      {
        preserveScroll: true,
        onSuccess: () => setErrors([]),
        onError: (err) => {
          const msgs = Object.values(err || {}).flat().map((m) => String(m));
          setErrors(msgs.length ? msgs : ['Erro ao enviar para a tutela.']);
          window.scrollTo(0, 0);
        },
      },
    );
  };

  const handleDecision = (solicitacaoId, decisao) => {
    router.post(
      processarDecisao(solicitacaoId).url,  
      { decisao },
      {
        preserveScroll: true,
        onSuccess: () => setErrors([]),
        onError: (err) => {
          const msgs = Object.values(err || {}).flat().map((m) => String(m));
          setErrors(msgs.length ? msgs : ['Ocorreu um erro ao processar a decisão.']);
          window.scrollTo(0, 0);
        },
      },
    );
  };

  const getResponsibleLabel = (solicitacao) => {
    if (solicitacao.responsavel_instituicao_id === solicitacao.instituicao_tutora_id) {
      return 'A cargo do instituto';
    }
    return 'A cargo do colégio';
  };

  return (
    <div className="space-y-6 p-6">
      <div className="flex items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl font-semibold">Solicitar Documentos</h1>
          <p className="text-sm text-muted-foreground">
            Revise os pedidos do colégio: decida diretamente os documentos normais
            e encaminhe apenas os certificados para a tutela.
          </p>
        </div>

        <div>
        <RequestHistoryDrawer viewType="received" items={ver === 'historico' ? solicitacoes : undefined} />
        </div>
      </div>

      <div className="space-y-4">
        {errors.length > 0 && <AlertError errors={errors} title="Erro" />}
        {solicitacoes.length === 0 && (
          <Card className="border-0">
            <CardContent className="py-8 text-sm text-muted-foreground">
              Nenhuma solicitação pendente no colégio.
            </CardContent>
          </Card>
        )}

        {solicitacoes.map((solicitacao) => {
          const currentStatus = resolveSolicitacaoStatus(solicitacao);
          const statusLabel =
            solicitacaoDocumentoStatusLabels[currentStatus] ??
            currentStatus;
          const isCertificado = solicitacao.tipo_documento === 'certificado';
          const podeEnviarParaTutela = isCertificado && currentStatus === 'pendente';
          const podeDecidirDirectamente =
            !isCertificado && currentStatus === 'pendente' && Boolean(solicitacao.can_decidir);
          const isResponsible = Boolean(solicitacao.can_marcar_pago || solicitacao.can_marcar_pronto);
          const showPaymentAction =
            currentStatus === 'aprovado' &&
            solicitacao.estado_pagamento !== 'pago' &&
            Boolean(solicitacao.can_marcar_pago);
          const showReadyAction =
            currentStatus === 'pago' &&
            !solicitacao.data_emissao &&
            Boolean(solicitacao.can_marcar_pronto);
          const showCollectionAction =
            currentStatus === 'pronto' &&
            !solicitacao.data_levantamento &&
            isResponsible;

          return (
            <Card key={solicitacao.id} className="border-0">
              <CardHeader>
                <div className="flex items-center justify-between gap-3">
                  <CardTitle className="text-base">
                    {solicitacao.aluno ?? 'Aluno'} - {solicitacao.tipo_label}
                  </CardTitle>
                  <Badge
                    className={
                      solicitacaoDocumentoStatusClassNames[currentStatus] ??
                      'bg-slate-100 text-slate-700'
                    }
                  >
                    {statusLabel}
                  </Badge>
                </div>
                <CardDescription>
                  Processo {solicitacao.numero_processo ?? 'a gerar'} •{' '}
                  {solicitacao.created_at}
                </CardDescription>
              </CardHeader>

              <CardContent className="space-y-4">
                <p className="text-sm text-muted-foreground">
                  Motivo: {solicitacao.motivo}
                </p>

                {solicitacao.observacoes && (
                  <p className="text-sm text-muted-foreground">
                    Observações: {solicitacao.observacoes}
                  </p>
                )}

                {['pendente', 'aprovado', 'pago', 'pronto', 'entregue'].includes(currentStatus) && (
                  <div className="mt-4 flex flex-wrap items-center gap-2 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
                    {[
                      { label: 'Pendente', complete: currentStatus !== 'pendente' },
                      { label: 'Aprovado', complete: ['aprovado', 'pago', 'pronto', 'entregue'].includes(currentStatus) },
                      { label: 'Pago', complete: ['pago', 'pronto', 'entregue'].includes(currentStatus) },
                      { label: 'Pronto', complete: ['pronto', 'entregue'].includes(currentStatus) },
                      { label: 'Levantado', complete: currentStatus === 'entregue' },
                    ].map((step, index) => (
                      <div key={step.label} className="flex items-center gap-2">
                        <span
                          className={
                            step.complete
                              ? 'rounded-full bg-green-100 px-2 py-1 text-green-800'
                              : 'rounded-full bg-slate-100 px-2 py-1 text-slate-500'
                          }
                        >
                          {step.label}
                        </span>
                        {index < 3 && <span className="text-slate-400">→</span>}
                      </div>
                    ))}
                  </div>
                )}

                {currentStatus === 'rejeitado' ? (
                  <p className="text-sm text-muted-foreground">
                    Pedido rejeitado{isCertificado ? ' pela tutela.' : '.'}
                  </p>
                ) : (
                  <div className="flex flex-wrap items-center gap-2">
                    {podeEnviarParaTutela && (
                      <Button onClick={() => handleEnviar(solicitacao.id)}>
                        {solicitacao.encaminhado_para_tutela
                          ? 'Reenviar à tutela'
                          : 'Enviar à tutela'}
                      </Button>
                    )}

                    {isCertificado && currentStatus === 'pendente' && solicitacao.encaminhado_para_tutela && (
                      <p className="text-xs text-muted-foreground">
                        Aguardando decisão da tutela.
                      </p>
                    )}

                    {podeDecidirDirectamente && (
                      <>
                        <Button onClick={() => handleDecision(solicitacao.id, 'aprovado')}>
                          Aprovar
                        </Button>
                        <Button
                          variant="destructive"
                          onClick={() => handleDecision(solicitacao.id, 'rejeitado')}
                        >
                          Rejeitar
                        </Button>
                      </>
                    )}

                    {showPaymentAction && (
                      <Button
                        variant="secondary"
                        onClick={() => {
                          if (!confirm('Deseja marcar este documento como pago?')) return;
                          router.post(marcarComoPagoAction(solicitacao.id).url, {}, {
                            preserveScroll: true,
                            onSuccess: () => setErrors([]),
                            onError: (err) => {
                              const msgs = Object.values(err || {}).flat().map((m) => String(m));
                              setErrors(msgs.length ? msgs : ['Erro ao marcar como pago.']);
                              window.scrollTo(0, 0);
                            },
                          });
                        }}
                      >
                        Marcar como pago
                      </Button>
                    )}

                    {showReadyAction && (
                      <Button
                        variant="outline"
                        onClick={() => {
                          if (!confirm('Deseja marcar este documento como pronto?')) return;
                          router.post(
                            emitir(solicitacao.id).url,
                            {
                              numero_registro_tutora:
                                solicitacao.numero_registro_tutora || 'REGISTO MANUAL',
                            },
                            {
                              preserveScroll: true,
                              onSuccess: () => setErrors([]),
                              onError: (err) => {
                                const msgs = Object.values(err || {}).flat().map((m) => String(m));
                                setErrors(msgs.length ? msgs : ['Erro ao marcar como pronto.']);
                                window.scrollTo(0, 0);
                              },
                            },
                          );
                        }}
                      >
                        Marcar documento pronto
                      </Button>
                    )}

                    {showCollectionAction && (
                      <Button
                        variant="outline"
                        onClick={() => {
                          if (!confirm('Deseja registar o levantamento do documento?')) return;
                          router.post(marcarComoLevantado(solicitacao.id).url, {}, {
                            preserveScroll: true,
                            onSuccess: () => setErrors([]),
                            onError: (err) => {
                              const msgs = Object.values(err || {}).flat().map((m) => String(m));
                              setErrors(msgs.length ? msgs : ['Erro ao registar levantamento.']);
                              window.scrollTo(0, 0);
                            },
                          });
                        }}
                      >
                        Marcar como levantado
                      </Button>
                    )}

                    {!isResponsible && currentStatus === 'aprovado' && (
                      <p className="text-xs text-muted-foreground">
                        {getResponsibleLabel(solicitacao)}
                      </p>
                    )}
                  </div>
                )}
                {solicitacao.encaminhado_para_tutela && (
                  <p className="text-xs text-muted-foreground">
                    Encaminhado à tutela em {solicitacao.encaminhado_em}
                  </p>
                )}
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}