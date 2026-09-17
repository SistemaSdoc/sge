import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { ArrowLeft, Check, X } from 'lucide-react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  index,
  aprovarTutela,
  rejeitarTutela,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

// ─────────────────────────────────────────────────────────────
// Campos técnicos que NUNCA devem aparecer ao utilizador
// ─────────────────────────────────────────────────────────────
const CAMPOS_OCULTOS = new Set([
  'tipo',
  'titulo',
  'mensagem',
  'status',
  'url',
  'comentario',
  'data',
  'grupo_pap_id',
  'troca_tutela_final',
  'curso_tutelado_shared_id',
  'curso_tutelado_shared_anterior_id',
  'tenant_tutor_anterior_id',
  'tenant_tutelado_id',
  'resultado',
  'assinatura',
]);

// ─────────────────────────────────────────────────────────────
// Labels amigáveis para os campos que mostramos
// ─────────────────────────────────────────────────────────────
const LABELS = {
  prazo_titulo: 'Prazo',
  disciplina: 'Disciplina',
  classe: 'Classe',
  turma: 'Turma',
  versao: 'Versão',
  professor_nome: 'Professor',
  motivo: 'Motivo',
  parecer_diretor: 'Parecer do diretor',
  estado: 'Estado',
  estado_label: 'Estado',
  status_label: 'Estado',
  data_limite: 'Data limite',
  periodo: 'Período',
  valor_total: 'Valor total',
  meses: 'Meses em atraso',
  submissao_id: 'Submissão',
  justificativa_id: 'Justificativa',
  prazo_id: 'Prazo',
};

const labelDe = (key) =>
  LABELS[key] ?? key.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());

const deveMostrar = (key) => {
  // Esconde tudo o que termina em _id
  if (/_id$/.test(key)) return false;

  // Esconde campos explicitamente listados
  if (CAMPOS_OCULTOS.has(key)) return false;

  return true;
};

export default function Show({ notificacao }) {
  const [decidido, setDecidido] = useState(false);

  const isTutelaAction = [
    'solicitacao_tutela',
    'troca_tutela',
    'conversao_tutela_propria',
    'conversao_tutela_propria_pendente',
    'troca_tutela_rejeitada',
    'troca_tutela_resultado',
    'conversao_tutela_propria_resultado',
  ].includes(notificacao.tipo);

  const podeDecidirTipo = [
    'solicitacao_tutela',
    'troca_tutela',
    'conversao_tutela_propria',
  ].includes(notificacao.tipo);

  const temReferenciaCentral = Boolean(
    notificacao.dados?.curso_tutelado_shared_id,
  );

  const status = notificacao.dados?.status ?? 'pendente';
  const isPendente = ['pendente', 'pendente_troca'].includes(status);
  const podeDecidir =
    podeDecidirTipo && temReferenciaCentral && isPendente && !decidido;
  const isTrocaTutela = notificacao.tipo === 'troca_tutela';

  const statusConfig = {
    pendente: { label: 'Pendente', variant: 'outline' },
    pendente_troca: {
      label: 'Pendente de aprovação da instituição atual',
      variant: 'outline',
    },
    activo: { label: 'Aprovada', variant: 'secondary' },
    aprovada: { label: 'Aprovada', variant: 'secondary' },
    aprovada_instituicao_anterior: {
      label: 'Aprovada pela instituição anterior',
      variant: 'secondary',
    },
    troca_tutela_rejeitada: {
      label: 'Troca rejeitada',
      variant: 'destructive',
    },
    rejeitado: { label: 'Rejeitada', variant: 'destructive' },
    rejeitada: { label: 'Rejeitada', variant: 'destructive' },
    encerrado: { label: 'Encerrada', variant: 'outline' },
    conversao_tutela_propria_resultado: {
      label: 'Conversão decidida',
      variant: 'secondary',
    },
  };
  const statusAtual = statusConfig[status] ?? {
    label: 'Pendente',
    variant: 'outline',
  };

  // Constrói a lista de campos a mostrar
  const camposVisiveis = Object.entries(notificacao.dados ?? {})
    .filter(([key, value]) => {
      if (!deveMostrar(key)) return false;
      if (value === null || value === undefined || value === '') return false;
      if (Array.isArray(value) && value.length === 0) return false;
      return true;
    });

  return (
    <div className="mx-auto w-full max-w-3xl p-6">
      <Head title={notificacao.titulo || 'Notificação'} />

      <Link
        href={index().url}
        className="mb-6 inline-flex items-center gap-2 text-sm text-muted-foreground hover:text-foreground"
      >
        <ArrowLeft data-icon="inline-start" />
        Voltar às notificações
      </Link>

      <Card>
        <CardHeader>
          <div className="flex items-start justify-between gap-4">
            <div>
              <Badge variant="secondary">{notificacao.tipo}</Badge>
              <CardTitle className="mt-3">{notificacao.titulo}</CardTitle>
              <CardDescription className="mt-2">
                {notificacao.criada_em}
              </CardDescription>
            </div>
            <div className="flex flex-wrap items-center gap-2">
              {notificacao.lida && <Badge variant="outline">Lida</Badge>}
              {isTutelaAction && (
                <Badge variant={statusAtual.variant}>{statusAtual.label}</Badge>
              )}
            </div>
          </div>
        </CardHeader>

        <CardContent className="flex flex-col gap-6">
          <p className="text-sm leading-6">{notificacao.mensagem}</p>

          {camposVisiveis.length > 0 && (
            <div className="flex flex-col gap-4">
              {camposVisiveis.map(([key, value]) => (
                <div key={key} className="border-t pt-4">
                  <p className="text-xs font-medium text-muted-foreground uppercase">
                    {labelDe(key)}
                  </p>
                  <p className="mt-1 text-sm">
                    {Array.isArray(value) ? value.join(', ') : String(value)}
                  </p>
                </div>
              ))}
            </div>
          )}

          {podeDecidir && (
            <div className="flex flex-wrap gap-3 border-t pt-6">
              <Button
                type="button"
                onClick={() => {
                  setDecidido(true);
                  router.post(aprovarTutela(notificacao.id).url);
                }}
              >
                <Check data-icon="inline-start" />
                {isTrocaTutela ? 'Aprovar troca de tutela' : 'Aprovar tutela'}
              </Button>
              <Button
                type="button"
                variant="outline"
                onClick={() => {
                  setDecidido(true);
                  router.post(rejeitarTutela(notificacao.id).url);
                }}
              >
                <X data-icon="inline-start" />
                {isTrocaTutela ? 'Rejeitar troca' : 'Rejeitar solicitação'}
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}