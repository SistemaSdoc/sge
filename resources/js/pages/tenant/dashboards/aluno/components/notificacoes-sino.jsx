import { useEffect, useState, useCallback } from 'react';
import { BellIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  index,
  marcarLida,
  marcarTodasLidas,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

const INTERVALO_POLLING = 30000; // 30s

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

export default function NotificacoesSino() {
  const [notificacoes, setNotificacoes] = useState([]);
  const [naoLidas, setNaoLidas] = useState(0);
  const [aberto, setAberto] = useState(false);

  const carregar = useCallback(async () => {
    try {
      const res = await fetch(index().url, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      setNotificacoes(data.notificacoes);
      setNaoLidas(data.nao_lidas);
    } catch (e) {
      // silencioso — não interrompe a UI por falha de polling
    }
  }, []);

  useEffect(() => {
    carregar();
    const intervalo = setInterval(carregar, INTERVALO_POLLING);
    return () => clearInterval(intervalo);
  }, [carregar]);

  const handleMarcarTodasLidas = async () => {
    await fetch(marcarTodasLidas().url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ?.content,
      },
    });
    carregar();
  };

  return (
    <Popover open={aberto} onOpenChange={setAberto}>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <BellIcon className="size-5" />
          {naoLidas > 0 && (
            <Badge
              variant="destructive"
              className="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full p-0 text-[10px]"
            >
              {naoLidas > 9 ? '9+' : naoLidas}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>

      <PopoverContent align="end" className="w-80 p-0">
        <div className="flex items-center justify-between border-b p-3">
          <span className="text-sm font-medium">Notificações</span>
          {naoLidas > 0 && (
            <Button variant="ghost" size="sm" onClick={handleMarcarTodasLidas}>
              Marcar todas como lidas
            </Button>
          )}
        </div>

        <div className="max-h-96 overflow-y-auto">
          {notificacoes.length === 0 ? (
            <p className="p-4 text-center text-sm text-muted-foreground">
              Sem notificações.
            </p>
          ) : (
            notificacoes.map((n) => (
              <div
                key={n.id}
                className={`w-full border-b p-3 text-left last:border-0 ${
                  n.lida ? 'opacity-60' : ''
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <p className="text-sm font-medium">{n.titulo}</p>
                  {!n.lida && (
                    <span className="mt-1 size-2 shrink-0 rounded-full bg-destructive" />
                  )}
                </div>

                <p className="text-xs text-muted-foreground">{n.mensagem}</p>

                {n.tipo === 'propina_atraso' && n.meses?.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {n.meses.map((mes, i) => (
                      <Badge
                        key={i}
                        variant="destructive"
                        className="font-normal"
                      >
                        {mes}
                      </Badge>
                    ))}
                  </div>
                )}

                {n.tipo === 'propina_atraso' && n.valor_total != null && (
                  <p className="mt-1 text-xs font-medium">
                    Total: {formatCurrency(n.valor_total)}
                  </p>
                )}

                {n.tipo === 'propina_atraso' && (
                  <p className="mt-1 text-[10px] text-muted-foreground italic">
                    Resolve-se automaticamente após o pagamento
                  </p>
                )}

                <p className="mt-1 text-[10px] text-muted-foreground">
                  {n.criada_em}
                </p>
              </div>
            ))
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}
