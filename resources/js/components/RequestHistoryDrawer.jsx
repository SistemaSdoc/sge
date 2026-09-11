import React, { useEffect, useState } from 'react';
import { usePage } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Drawer,
  DrawerTrigger,
  DrawerContent,
  DrawerHeader,
  DrawerTitle,
  DrawerClose,
} from '@/components/ui/drawer';
import { Inbox, Send, X } from 'lucide-react';
import { CardDescription } from '@/components/ui/card';
import {
  resolveSolicitacaoStatus,
  solicitacaoDocumentoStatusLabels,
  solicitacaoDocumentoStatusClassNames,
} from '@/utils/solicitacao-documento-status';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Spinner } from '@/components/ui/spinner';
import { Empty, EmptyDescription } from '@/components/ui/empty';

export default function RequestHistoryDrawer({ viewType = 'sent', items = null }) {
  const page = usePage();
  const props = page.props || {};

  const [open, setOpen] = useState(false);
  const [data, setData] = useState(items || null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);

  // Determine title per spec
  const title = viewType === 'sent' ? 'Solicitações Feitas' : 'Solicitações Recebidas';
  const isStudentView = viewType === 'sent';
  const triggerIcon = isStudentView ? Send : Inbox;
  const triggerVariant = isStudentView ? 'default' : 'secondary';
  const triggerClassName = 'shadow-none transition-all duration-200 hover:-translate-y-0.5 hover:shadow-sm';

  // Helper to compute default data from page props when items not provided
  const computeFallbackData = () => {
    if (items) return items;
    if (viewType === 'sent') {
      return props.solicitacoes ?? [];
    }

    if (viewType === 'received') {
      if (Array.isArray(props.solicitacoes_locais) && Array.isArray(props.solicitacoes_tuteladas)) {
        return [...props.solicitacoes_locais, ...props.solicitacoes_tuteladas];
      }
      return props.solicitacoes ?? props.solicitacoes_locais ?? props.solicitacoes_tuteladas ?? [];
    }

    return [];
  };

  // On mount, set fallback data if available
  useEffect(() => {
    if (data === null) {
      setData(computeFallbackData());
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, []);

  // Fetch dynamic data when drawer opens, if items not provided (user requested dynamic fetch)
  useEffect(() => {
    let cancelled = false;

    const fetchData = async () => {
      if (items) return; // parent provided items — do not fetch
      setLoading(true);
      setError(null);

      try {
        const res = await fetch('/dashboard/solicitacoes-documentos/history', {
          headers: { Accept: 'application/json' },
          credentials: 'same-origin',
        });

        if (!res.ok) {
          throw new Error(`HTTP ${res.status}`);
        }

        const json = await res.json();
        if (!cancelled) {
          setData(Array.isArray(json.data) ? json.data : []);
        }
      } catch (err) {
        if (!cancelled) {
          setError(err.message || 'Erro ao carregar histórico');
        }
      } finally {
        if (!cancelled) setLoading(false);
      }
    };

    if (open) {
      fetchData();
    }

    return () => {
      cancelled = true;
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open]);

  // O contador de pendentes foi removido por decisão: o botão não mostra mais badge.

  // Render list item
  const renderItem = (s) => {
    const status = resolveSolicitacaoStatus(s);
    const statusLabel = solicitacaoDocumentoStatusLabels[status] ?? status;
    const statusClass = solicitacaoDocumentoStatusClassNames[status] ?? 'bg-slate-100 text-slate-700';

    return (
      <div key={s.id} className="border-b py-3 last:border-b-0">
        <div className="flex items-start justify-between gap-3">
          <div className="min-w-0">
            {/* Para instituições: mostrar Nome do estudante e Número de processo (apenas em vista de instituições) */}
            {!isStudentView && s.aluno && (
              <div className="text-sm font-medium">Nome do estudante: {s.aluno}</div>
            )}
            {!isStudentView && s.numero_processo && (
              <div className="text-xs text-muted-foreground">Número de processo: {s.numero_processo}</div>
            )}

            <div className="text-sm font-medium">{s.tipo_label ?? s.tipo_documento}</div>
            <div className="text-xs text-muted-foreground truncate">{s.motivo}</div>
            <div className="mt-1 text-xs text-muted-foreground">{s.created_at}</div>
          </div>
          <div className="shrink-0">
            <Badge className={statusClass}>{statusLabel}</Badge>
          </div>
        </div>
      </div>
    );
  };

  return (
    <Drawer direction="right" onOpenChange={(val) => setOpen(val)}>
      <DrawerTrigger asChild>
        <Button
          type="button"
          variant={triggerVariant}
          size="sm"
          aria-label="Histórico de Solicitações"
          title="Clique para ver histórico de solicitações"
          className={`gap-2 px-6 text-sm font-medium ${triggerClassName}`}
        >
          {React.createElement(triggerIcon, { className: 'size-4' })}
          <span>Histórico de Solicitações</span>
        </Button>
      </DrawerTrigger>

      <DrawerContent>
        <DrawerHeader className="relative border-b py-3">
          <div>
            <DrawerTitle>{title}</DrawerTitle>
            <CardDescription className="mt-1">{isStudentView ? 'Consulte as suas solicitações.' : 'Consulte as solicitações recebidas pela instituição.'}</CardDescription>
          </div>

          <div className="absolute right-3 top-3">
            <DrawerClose asChild>
              <Button variant="ghost" size="sm" aria-label="Fechar painel">
                <X className="size-4" />
              </Button>
            </DrawerClose>
          </div>
        </DrawerHeader>

        <div className="p-4 overflow-auto max-h-[70vh]">
          {loading ? (
            <div className="flex items-center gap-2">
              <Spinner />
              <CardDescription>A carregar o histórico de solicitações…</CardDescription>
            </div>
          ) : error ? (
            <Alert variant="destructive">
              <AlertDescription>Não foi possível carregar o histórico de solicitações. Tente novamente mais tarde.</AlertDescription>
            </Alert>
          ) : (!data || data.length === 0) ? (
            <Empty>
              <EmptyDescription>Ainda não há solicitações para exibir.</EmptyDescription>
            </Empty>
          ) : (
            data.map((s) => renderItem(s))
          )}
        </div>
      </DrawerContent>
    </Drawer>
  );
}
