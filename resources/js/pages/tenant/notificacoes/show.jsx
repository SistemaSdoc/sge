import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
} from '@/components/ui/card';
import { NotificacaoItem } from '@/components/notificacao-item';
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
  'intro',
  'linhas',
  'diretrizes',
  'fecho',
  'assinatura',
  'status',
  'url',
  'comentario',
  'data',
  'dados',
  'prazos',
  'grupo_pap_id',
  'troca_tutela_final',
  'curso_tutelado_shared_id',
  'curso_tutelado_shared_anterior_id',
  'tenant_tutor_anterior_id',
  'tenant_tutelado_id',
  'resultado',
]);

// ─────────────────────────────────────────────────────────────
// Labels amigáveis
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
  LABELS[key] ??
  key.replaceAll('_', ' ').replace(/\b\w/g, (c) => c.toUpperCase());

// ─────────────────────────────────────────────────────────────
// Filtro — esconde campos técnicos e objectos aninhados
// ─────────────────────────────────────────────────────────────
const deveMostrar = (key, value) => {
  if (/_id$/.test(key)) return false;
  if (CAMPOS_OCULTOS.has(key)) return false;

  // Esconde objectos aninhados
  if (value !== null && typeof value === 'object' && !Array.isArray(value)) {
    return false;
  }

  // Esconde arrays de objectos
  if (
    Array.isArray(value) &&
    value.length > 0 &&
    value.some((v) => v !== null && typeof v === 'object')
  ) {
    return false;
  }

  return true;
};

// ─────────────────────────────────────────────────────────────
// Render seguro — nunca devolve "[object Object]"
// ─────────────────────────────────────────────────────────────
function renderValor(value) {
  if (value === null || value === undefined || value === '') {
    return <span className="text-muted-foreground">—</span>;
  }

  if (Array.isArray(value)) {
    if (value.every((v) => v === null || typeof v !== 'object')) {
      return value.join(', ');
    }
    return null;
  }

  if (typeof value === 'object') {
    return null;
  }

  return String(value);
}

// ─────────────────────────────────────────────────────────────
// Componente
// ─────────────────────────────────────────────────────────────
export default function Show({ notificacao }) {
  const [decidido, setDecidido] = useState(false);

  const dados = notificacao.dados ?? notificacao.data ?? {};

  // É uma notificação formal de prazo?
  const isFormalPrazo = Boolean(
    dados.intro || dados.linhas || dados.diretrizes || dados.fecho,
  );

  // ─── Lógica de tutela ───
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

  const temReferenciaCentral = Boolean(dados.curso_tutelado_shared_id);
  const status = dados.status ?? 'pendente';
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
      variant: 'outline',
    },
    rejeitado: { label: 'Rejeitada', variant: 'outline' },
    rejeitada: { label: 'Rejeitada', variant: 'outline' },
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

  // Campos custom (só para tutela — prazo usa o NotificacaoItem)
  const camposVisiveis = !isFormalPrazo
    ? Object.entries(dados).filter(([key, value]) => {
        if (!deveMostrar(key, value)) return false;
        if (value === null || value === undefined || value === '') return false;
        if (Array.isArray(value) && value.length === 0) return false;
        return true;
      })
    : [];

  return (
    <>
      <Head title={notificacao.titulo ?? 'Notificação'} />

      <div className="mx-auto w-full max-w-3xl space-y-4 p-4 sm:p-6">
        <Card className="gap-0">
          {/* Header */}
          <CardHeader className="border-b border-foreground/10">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
              <div className="min-w-0">
                <Breadcrumb>
                  <BreadcrumbList className="flex-wrap">
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbLink asChild>
                        <Link
                          href={index().url}
                          className="line-clamp-2 text-sm font-semibold text-primary"
                        >
                          Notificações
                        </Link>
                      </BreadcrumbLink>
                    </BreadcrumbItem>
                    <BreadcrumbSeparator />
                    <BreadcrumbItem className="min-w-0">
                      <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                        Detalhe
                      </BreadcrumbPage>
                    </BreadcrumbItem>
                  </BreadcrumbList>
                </Breadcrumb>
                <CardDescription className="mt-1">
                  {notificacao.criada_em ?? '—'}
                </CardDescription>
              </div>

              <CardAction className="flex flex-wrap items-center gap-2">
                {notificacao.lida && (
                  <Badge variant="outline" className="text-[10px]">
                    Lida
                  </Badge>
                )}
                {isTutelaAction && (
                  <Badge variant={statusAtual.variant} className="text-[10px]">
                    {statusAtual.label}
                  </Badge>
                )}
                <Button variant="outline" size="sm" asChild>
                  <Link href={index().url}>Voltar</Link>
                </Button>
              </CardAction>
            </div>
          </CardHeader>

          <CardContent className="p-0!">
            {/* ─── Formato formal (prazo) ─── */}
            {isFormalPrazo ? (
              <NotificacaoItem
                notificacao={{ ...notificacao, data: dados }}
                variant="full"
                onClick={() => {}}
              />
            ) : (
              /* ─── Formato tutela / outro ─── */
              <div className="space-y-5 p-4 sm:p-6">
                <div className="space-y-2">
                  {notificacao.titulo && (
                    <h3 className="text-base leading-snug font-semibold">
                      {notificacao.titulo}
                    </h3>
                  )}
                  {notificacao.mensagem && (
                    <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
                      {notificacao.mensagem}
                    </p>
                  )}
                </div>

                {camposVisiveis.length > 0 && (
                  <div className="divide-y border">
                    {camposVisiveis.map(([key, value]) => {
                      const conteudo = renderValor(value);
                      if (conteudo === null) return null;

                      return (
                        <div key={key} className="px-4 py-3">
                          <p className="text-[11px] font-medium tracking-wide text-muted-foreground uppercase">
                            {labelDe(key)}
                          </p>
                          <p className="mt-1 text-sm">{conteudo}</p>
                        </div>
                      );
                    })}
                  </div>
                )}

                {podeDecidir && (
                  <div className="flex flex-wrap gap-2 border-t pt-4">
                    <Button
                      type="button"
                      onClick={() => {
                        setDecidido(true);
                        router.post(aprovarTutela(notificacao.id).url);
                      }}
                      className="hover:cursor-pointer"
                    >
                      {isTrocaTutela
                        ? 'Aprovar troca de tutela'
                        : 'Aprovar tutela'}
                    </Button>
                    <Button
                      type="button"
                      variant="outline"
                      onClick={() => {
                        setDecidido(true);
                        router.post(rejeitarTutela(notificacao.id).url);
                      }}
                      className="hover:cursor-pointer"
                    >
                      {isTrocaTutela
                        ? 'Rejeitar troca'
                        : 'Rejeitar solicitação'}
                    </Button>
                  </div>
                )}
              </div>
            )}
          </CardContent>
        </Card>
      </div>
    </>
  );
}
