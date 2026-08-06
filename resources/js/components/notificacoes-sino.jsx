import { useEffect, useState, useCallback } from 'react';
import { BellIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import { index, marcarLida, marcarTodasLidas } from '@/actions/App/Http/Controllers/NotificacaoController';

const INTERVALO_POLLING = 30000; // 30s

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

  const handleMarcarLida = async (id) => {
    await fetch(marcarLida(id).url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
      },
    });
    carregar();
  };

  const handleMarcarTodasLidas = async () => {
    await fetch(marcarTodasLidas().url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
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
              className="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full p-0 text-[10px]"
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

        <div className="max-h-80 overflow-y-auto">
          {notificacoes.length === 0 ? (
            <p className="p-4 text-center text-sm text-muted-foreground">
              Sem notificações.
            </p>
          ) : (
            notificacoes.map((n) => (
              <button
                key={n.id}
                onClick={() => handleMarcarLida(n.id)}
                className={`w-full border-b p-3 text-left last:border-0 hover:bg-muted/50 ${
                  n.lida ? 'opacity-60' : ''
                }`}
              >
                <div className="flex items-start justify-between gap-2">
                  <p className="text-sm font-medium">{n.titulo}</p>
                  {!n.lida && <span className="mt-1 size-2 shrink-0 rounded-full bg-destructive" />}
                </div>
                <p className="text-xs text-muted-foreground">{n.mensagem}</p>
                <p className="mt-1 text-[10px] text-muted-foreground">{n.criada_em}</p>
              </button>
            ))
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}