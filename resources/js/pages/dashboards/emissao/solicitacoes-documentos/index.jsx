import { useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LayersIcon } from 'lucide-react';
import AlertError from '@/components/alert-error';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { emitir, marcarComoPagoAction, marcarComoLevantado } from '@/actions/App/Http/Controllers/Tenant/SolicitacaoDocumentoController';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';
import RequestHistoryDrawer from '@/components/RequestHistoryDrawer';

export default function EmissaoSolicitacoesDocumentosPage() {
  const { solicitacoes = [], ver = null } = usePage().props;
  const form = useForm({ numero_registro_tutora: '' });
  const [errors, setErrors] = useState([]);

  const handleEmitir = (solicitacaoId) => {
    router.post(
      emitir(solicitacaoId).url,
      { numero_registro_tutora: form.data.numero_registro_tutora },
      {
        preserveScroll: true,
        onSuccess: () => setErrors([]),
        onError: (err) => {
          const msgs = Object.values(err || {}).flat().map((m) => String(m));
          setErrors(msgs.length ? msgs : ['Erro ao emitir o documento.']);
          window.scrollTo(0, 0);
        },
      },
    );
  };

  return (
    <div className="space-y-6 p-4 sm:p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-xl font-semibold sm:text-2xl">Emissão de documentos</h1>
        </div>

        <div className="shrink-0">
          <RequestHistoryDrawer viewType="received" items={ver === 'historico' ? solicitacoes : undefined} />
        </div>
      </div>

      <div className="space-y-4">
        {errors.length > 0 && <AlertError errors={errors} title="Erro" />}
        {solicitacoes.length === 0 && (
          <Card className="border-0">
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title="Nenhuma solicitação aprovada"
              description="Não existem documentos aprovados para emissão neste momento."
            />
          </Card>
        )}

        {solicitacoes.map((solicitacao) => {
          const currentStatus = resolveSolicitacaoStatus(solicitacao);
          const statusLabel =
            solicitacaoDocumentoStatusLabels[currentStatus] ??
            currentStatus;

          return (
            <Card key={solicitacao.id} className="border-0">
              <CardHeader>
                <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
                  <CardTitle className="wrap-break-words text-base">
                    {solicitacao.aluno} — {solicitacao.tipo_label}
                  </CardTitle>
                  <Badge
                    className={`shrink-0 ${
                      solicitacaoDocumentoStatusClassNames[
                        currentStatus
                      ] ??
                      'bg-slate-100 text-slate-700'
                    }`}
                  >
                    {statusLabel}
                  </Badge>
                </div>
                <CardDescription>
                  Processo {solicitacao.numero_processo} •{' '}
                  {solicitacao.created_at}
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <p className="wrap-break-words text-sm text-muted-foreground">
                  Motivo: {solicitacao.motivo}
                </p>

                {solicitacao.observacoes && (
                  <p className="wrap-break-words text-sm text-muted-foreground">
                    Observações: {solicitacao.observacoes}
                  </p>
                )}

                {solicitacao.numero_registro_tutora && (
                  <p className="wrap-break-words text-sm text-muted-foreground">
                    Nº de registo: {solicitacao.numero_registro_tutora}
                  </p>
                )}

                {solicitacao.data_levantamento && (
                  <p className="wrap-break-words text-sm text-muted-foreground">
                    Levantado em {solicitacao.data_levantamento}
                  </p>
                )}

                {['pendente', 'aprovado', 'pago', 'pronto', 'entregue'].includes(currentStatus) && (
                  <div className="flex flex-wrap items-center gap-x-2 gap-y-2 text-[10px] font-medium uppercase tracking-wide text-muted-foreground">
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

                {currentStatus === 'rejeitado' && (
                  <p className="text-sm text-muted-foreground">
                    Pedido rejeitado.
                  </p>
                )}

                <div className="space-y-2">
                  <Label htmlFor={`registo-${solicitacao.id}`}>
                    Número de registo da tutela
                  </Label>
                  <Input
                    id={`registo-${solicitacao.id}`}
                    value={form.data.numero_registro_tutora}
                    onChange={(event) =>
                      form.setData('numero_registro_tutora', event.target.value)
                    }
                    placeholder="Ex.: REG/2026/0001"
                  />
                </div>

                <div className="flex flex-col gap-2 sm:flex-row sm:flex-wrap">
                  <Button onClick={() => handleEmitir(solicitacao.id)}>
                    Emitir documento
                  </Button>

                  {solicitacao.estado_pagamento !== 'pago' && (
                    <Button
                      variant="secondary"
                      onClick={() => {
                        if (!confirm('Deseja marcar este documento como pago?')) return;
                        router.post(marcarComoPagoAction(solicitacao.id).url, {}, {
                          onSuccess: () => setErrors([]),
                          onError: (err) => {
                            const msgs = Object.values(err || {}).flat().map((m) => String(m));
                            setErrors(msgs.length ? msgs : ['Erro ao marcar como pago.']);
                            window.scrollTo(0, 0);
                          },
                        });
                      }}
                    >
                      Documento Pago
                    </Button>
                  )}

                  {solicitacao.can_marcar_levantado &&
                    solicitacao.documento_gerado &&
                    !solicitacao.data_levantamento && (
                      <Button
                        variant="outline"
                        onClick={() => {
                          if (!confirm('Deseja registar o levantamento do documento?')) return;
                          router.post(marcarComoLevantado(solicitacao.id).url, {}, {
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
                </div>
              </CardContent>
            </Card>
          );
        })}
      </div>
    </div>
  );
}