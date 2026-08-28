import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useDrawer } from '@/hooks/use-drawer';
import {
  index,
  marcarTodasLidas,
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
  const notificacoes = notificacoesProp ?? notificacoesState.notificacoes;
  const naoLidas = naoLidasProp ?? notificacoesState.naoLidas;
  const onRefresh = onRefreshProp ?? notificacoesState.carregar;
  const { closeDrawer } = useDrawer();

  const handleMarcarTodasLidas = () => {
    if (naoLidas === 0) {
      return;
    }

    router.post(
      marcarTodasLidas().url,
      {},
      {
        preserveScroll: true,
        preserveState: true,
        onSuccess: onRefresh,
        onError: onRefresh,
      },
    );
  };

  return (
    <div className="flex min-h-full flex-col">
      <div className="flex items-center justify-between">
        {naoLidas > 0 && (
          <Button
            type="button"
            variant="ghost"
            size="sm"
            onClick={handleMarcarTodasLidas}
          >
            Marcar todas como lidas
          </Button>
        )}
      </div>

      <div className="flex-1">
        {notificacoes.length === 0 ? (
          <p className="p-6 text-center text-sm text-muted-foreground">
            Sem notificações.
          </p>
        ) : (
          notificacoes.map((notificacao) => (
            <Link
              key={notificacao.id}
              href={show(notificacao.id).url}
              onClick={closeDrawer}
              className={`w-full border-b p-4 text-left transition-colors last:border-0 ${
                notificacao.lida
                  ? 'cursor-default opacity-60'
                  : 'cursor-pointer hover:bg-muted/50'
              }`}
            >
              <div className="flex items-start justify-between gap-3">
                <p className="text-sm font-medium">{notificacao.titulo}</p>
                {!notificacao.lida && (
                  <span className="mt-1 size-2 shrink-0 rounded-full bg-destructive" />
                )}
              </div>
              <p className="mt-1 text-xs text-muted-foreground">
                {notificacao.mensagem}
              </p>

              {notificacao.tipo === 'propina_atraso' &&
                notificacao.meses?.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {notificacao.meses.map((mes) => (
                      <Badge
                        key={mes}
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

              <p className="mt-2 text-[10px] text-muted-foreground">
                {notificacao.criada_em}
              </p>
            </Link>
          ))
        )}
      </div>
    </div>
  );
}

export function useNotificacoes() {
  const [notificacoes, setNotificacoes] = useState([]);
  const [naoLidas, setNaoLidas] = useState(0);

  const carregar = async () => {
    try {
      const response = await fetch(index().url, {
        headers: { Accept: 'application/json' },
      });
      const data = await response.json();

      setNotificacoes(data.notificacoes ?? []);
      setNaoLidas(data.nao_lidas ?? 0);
    } catch {
      // A área de notificações permanece disponível mesmo sem resposta da API.
    }
  };

  useEffect(() => {
    carregar();
    const intervalo = setInterval(carregar, 30000);

    return () => clearInterval(intervalo);
  }, []);

  return { notificacoes, naoLidas, carregar };
}
