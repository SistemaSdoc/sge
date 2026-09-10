import { useEffect, useState, useCallback } from 'react';
import { BellIcon, Trash2Icon } from 'lucide-react';
import { router } from '@inertiajs/react';
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
} from '@/actions/App/Http/Controllers/NotificacaoController';

const INTERVALO_POLLING = 30000;

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const categoriaLabel = {
  aluno: 'Aluno',
  instituicao: 'Instituição',
  tutela: 'Tutela',
};

// Normaliza a resposta da API para garantir que devolvemos sempre um array,
// mesmo que o backend devolva um objeto (ex: JSON com chaves não-sequenciais)
// ou algo inesperado por falha de rede/parsing.
const normalizarNotificacoes = (valor) => {
  if (Array.isArray(valor)) {
    return valor;
  }

  if (valor && typeof valor === 'object') {
    return Object.values(valor);
  }

  return [];
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
      setNotificacoes(normalizarNotificacoes(data.notificacoes));
      setNaoLidas(data.nao_lidas ?? 0);
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

  const handleMarcarLida = async (id) => {
    await fetch(marcarLida(id).url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ?.content,
      },
    });
  };

  const handleRemover = async (id) => {
    await fetch(`/dashboard/notificacoes/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ?.content,
      },
    });
    carregar();
  };

  const handleAbrir = async (n) => {
    if (n?.id) {
      await handleMarcarLida(n.id);
    }

    if (n?.url) {
      router.visit(n.url);
    }

    setAberto(false);
    carregar();
  };

  const grupos = {
    aluno: notificacoes.filter((n) => (n.categoria ?? 'aluno') === 'aluno'),
    instituicao: notificacoes.filter((n) => n.categoria === 'instituicao'),
    tutela: notificacoes.filter((n) => n.categoria === 'tutela'),
  };

  const renderGrupo = (chave, lista) => {
    if (!lista.length) {
      return null;
    }

    return (
      <div key={chave} className="border-b px-3 py-2 last:border-b-0">
        <p className="mb-2 text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
          {categoriaLabel[chave] ?? 'Geral'}
        </p>
        <div className="space-y-2">
          {lista.map((n) => (
            <div
              key={n.id}
              className={`rounded-md border p-2 text-left transition ${n.lida ? 'opacity-60' : 'bg-background'} ${n.url ? 'cursor-pointer hover:bg-muted/50' : ''}`}
              onClick={() => (n.url ? handleAbrir(n) : undefined)}
            >
              <div className="flex items-start justify-between gap-2">
                <div className="min-w-0 flex-1">
                  <p className="text-sm font-medium">
                    {n.titulo || 'Notificação'}
                  </p>
                </div>
                <div className="flex items-center gap-1">
                  {!n.lida && (
                    <span className="mt-1 size-2 shrink-0 rounded-full bg-destructive" />
                  )}
                  <button
                    type="button"
                    className="rounded p-1 text-muted-foreground hover:bg-muted"
                    onClick={(event) => {
                      event.stopPropagation();
                      handleRemover(n.id);
                    }}
                    aria-label="Eliminar notificação"
                  >
                    <Trash2Icon className="size-3.5" />
                  </button>
                </div>
              </div>

              <p className="mt-1 text-xs text-muted-foreground">{n.mensagem}</p>

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
          ))}
        </div>
      </div>
    );
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

      <PopoverContent align="end" className="w-105 p-0">
        <div className="flex items-center justify-between border-b p-3">
          <span className="text-sm font-medium">Notificações</span>
          {naoLidas > 0 && (
            <Button variant="ghost" size="sm" onClick={handleMarcarTodasLidas}>
              Marcar todas como lidas
            </Button>
          )}
        </div>

        <div className="max-h-112 overflow-y-auto">
          {notificacoes.length === 0 ? (
            <p className="p-4 text-center text-sm text-muted-foreground">
              Sem notificações.
            </p>
          ) : (
            <>
              {renderGrupo('aluno', grupos.aluno)}
              {renderGrupo('instituicao', grupos.instituicao)}
              {renderGrupo('tutela', grupos.tutela)}
            </>
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
}