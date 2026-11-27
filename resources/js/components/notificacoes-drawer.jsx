import { useCallback, useEffect, useMemo, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import { useDrawer } from '@/hooks/use-drawer';
import {
  index,
  markAllAsRead,
  sino,
  show,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);

  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

export function NotificacoesDrawer({
  notificacoes: notificacoesProp,
  naoLidas: naoLidasProp,
  onRefresh: onRefreshProp,
} = {}) {
  const notificacoesState = useNotificacoes();

  //   Uma única fonte de verdade
  const notificacoes = notificacoesProp ?? notificacoesState.notificacoes;
  const naoLidas = naoLidasProp ?? notificacoesState.naoLidas;
  const onRefresh = onRefreshProp ?? notificacoesState.carregar;

  const { closeDrawer } = useDrawer();

  //   Memoizar a lista filtrada (estável entre renders)
  const notificacoesNaoLidas = useMemo(
    () => notificacoes.filter((n) => !n.lida),
    [notificacoes],
  );

  //   Memoizar handler
  const handleMarcarTodasLidas = useCallback(() => {
    if (naoLidas === 0) return;

    router.post(
      markAllAsRead().url,
      {},
      {
        preserveScroll: true,
        preserveState: true,
      },
    );
  }, [naoLidas]);

  //   Handler estável para o Link (evita desmontagem prematura)
  const handleLinkClick = useCallback(() => {
    // setTimeout garante que o router.visit é processado
    // antes do drawer fechar
    setTimeout(closeDrawer, 0);
  }, [closeDrawer]);

  return (
    <div className="flex min-h-full flex-col">
      <div className="flex items-center justify-between gap-3 border-b px-4 py-3">
        <p className="text-xs text-muted-foreground">
          {naoLidas > 0
            ? `${naoLidas} ${naoLidas === 1 ? 'não lida' : 'não lidas'}`
            : 'Todas as notificações estão lidas'}
        </p>
        {/*   Ternário em vez de `&&` (evita `0` no DOM) */}
        {naoLidas > 0 ? (
          <Button
            type="button"
            variant="outline"
            size="sm"
            onClick={handleMarcarTodasLidas}
            className="hover:cursor-pointer"
          >
            Marcar todas como lidas
          </Button>
        ) : null}
      </div>

      <div className="flex-1">
        {notificacoesNaoLidas.length === 0 ? (
          <div className="flex flex-col items-center gap-3 px-6 py-12 text-center">
            <p className="text-sm text-muted-foreground">
              Sem notificações por ler.
            </p>
            <Button asChild variant="outline" size="sm">
              <Link href={index().url} onClick={handleLinkClick}>
                Abrir central de notificações
              </Link>
            </Button>
          </div>
        ) : (
          notificacoesNaoLidas.map((notificacao) => (
            <Link
              key={notificacao.id}
              href={show(notificacao.id).url}
              onClick={handleLinkClick}
              className={cn(
                'block border-b px-4 py-3 text-left transition-colors hover:bg-muted/50',
                'bg-muted/20',
              )}
            >
              <div className="flex min-w-0 items-center gap-2">
                <p className="min-w-0 flex-1 truncate text-sm font-medium">
                  {notificacao.titulo}
                </p>
                <p className="shrink-0 text-[10px] text-muted-foreground">
                  {notificacao.criada_em}
                </p>
                <span className="size-2 shrink-0 rounded-full bg-destructive" />
              </div>
              <p className="mt-1 line-clamp-2 text-xs whitespace-pre-line text-muted-foreground">
                {notificacao.mensagem}
              </p>

              {notificacao.tipo === 'propina_atraso' &&
                notificacao.meses?.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {/*   Deduplicar `meses` para evitar key duplicada */}
                    {[...new Set(notificacao.meses)].map((mes) => (
                      <Badge
                        key={`${notificacao.id}-${mes}`}
                        variant="destructive"
                        className="font-normal"
                      >
                        {mes}
                      </Badge>
                    ))}
                  </div>
                )}

              {notificacao.tipo === 'propina_atraso' &&
                notificacao.valor_total != null && (
                  <p className="mt-1 text-xs font-medium">
                    Total: {formatCurrency(notificacao.valor_total)}
                  </p>
                )}
            </Link>
          ))
        )}
      </div>
    </div>
  );
}

// ─────────────────────────────────────────────────────
// Hook — com singleton para evitar múltiplos setInterval
// ─────────────────────────────────────────────────────
let _intervaloGlobal = null;
let _listeners = new Set();

export function useNotificacoes() {
  const [notificacoes, setNotificacoes] = useState([]);
  const [naoLidas, setNaoLidas] = useState(0);

  const carregar = useCallback(async () => {
    try {
      const response = await fetch(sino().url, {
        headers: { Accept: 'application/json' },
      });
      const data = await response.json();
      setNotificacoes(data.notificacoes ?? []);
      setNaoLidas(data.nao_lidas ?? 0);
    } catch {
      // silencioso — a área de notificações fica disponível mesmo sem API
    }
  }, []);

  useEffect(() => {
    //   Registo do listener local
    _listeners.add(carregar);

    //   Um único intervalo global (não um por instância)
    if (!_intervaloGlobal) {
      _intervaloGlobal = setInterval(() => {
        _listeners.forEach((fn) => fn());
      }, 30000);
    }

    carregar();

    return () => {
      _listeners.delete(carregar);
      if (_listeners.size === 0 && _intervaloGlobal) {
        clearInterval(_intervaloGlobal);
        _intervaloGlobal = null;
      }
    };
  }, [carregar]);

  return { notificacoes, naoLidas, carregar };
}
