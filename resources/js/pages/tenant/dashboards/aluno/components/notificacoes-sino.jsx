import { useEffect, useState, useCallback } from 'react';
import { BellIcon, CheckCheck, ExternalLink } from 'lucide-react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  index,
  marcarLida,
  marcarTodasLidas,
<<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';
========
} from '@/actions/App/Http/Controllers/NotificacaoController';
>>>>>>>> Modulo-propinas:resources/js/pages/dashboards/aluno/components/notificacoes-sino.jsx

const INTERVALO_POLLING = 30000; // 30s

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};
=======
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const INTERVALO_POLLING = 30000;
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx

export default function NotificacoesSino() {
  const [notificacoes, setNotificacoes] = useState([]);
  const [naoLidas, setNaoLidas] = useState(0);
  const [aberto, setAberto] = useState(false);

  const carregar = useCallback(async () => {
    try {
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
      const res = await fetch(index().url, {
        headers: { Accept: 'application/json' },
      });
      const data = await res.json();
      setNotificacoes(data.notificacoes || []);
      setNaoLidas(data.nao_lidas || 0);
    } catch (e) {
      // silencioso
=======
      const res = await fetch('/notificacoes', {
        headers: { Accept: 'application/json' },
        credentials: 'same-origin',
      });

      if (!res.ok) {
        console.warn('Notificações indisponíveis', res.status);
        return;
      }

      const data = await res.json();
      setNotificacoes(Array.isArray(data?.notificacoes) ? data.notificacoes : []);
      setNaoLidas(Number(data?.nao_lidas) || 0);
    } catch (e) {
      console.warn('Erro ao carregar notificações', e);
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
    }
  }, []);

  useEffect(() => {
    carregar();
    const intervalo = setInterval(carregar, INTERVALO_POLLING);
    return () => clearInterval(intervalo);
  }, [carregar]);

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
<<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
  const handleMarcarTodasLidas = async () => {
    await fetch(marcarTodasLidas().url, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')
          ?.content,
========
  const handleMarcarLida = (id, url = null) => {
    router.post(marcarLida(id).url, {}, {
=======
  const handleMarcarLida = (id, url = null) => {
    router.post(`/notificacoes/${id}/ler`, {}, {
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
      preserveScroll: true,
      preserveState: true,
      onSuccess: () => {
        carregar();
        if (url) {
          setAberto(false);
          router.visit(url);
        }
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
>>>>>>>> Modulo-propinas:resources/js/pages/dashboards/aluno/components/notificacoes-sino.jsx
=======
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
      },
    });
  };

  const handleMarcarTodasLidas = () => {
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
    router.post(marcarTodasLidas().url, {}, {
=======
    router.post('/notificacoes/ler-todas', {}, {
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
      preserveScroll: true,
      preserveState: true,
      onSuccess: carregar,
    });
  };

  const handleClickNotificacao = (n) => {
    if (!n.lida) {
      handleMarcarLida(n.id, n.url);
    } else if (n.url) {
      setAberto(false);
      router.visit(n.url);
    }
  };

  return (
    <Popover open={aberto} onOpenChange={setAberto}>
      <PopoverTrigger asChild>
        <Button variant="ghost" size="icon" className="relative">
          <BellIcon className="size-5" />
          {naoLidas > 0 && (
            <Badge
              variant="destructive"
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
              className="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full p-0 text-[10px]"
=======
              className="absolute -right-1 -top-1 flex size-4 items-center justify-center rounded-full p-0 text-[10px]"
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
            >
              {naoLidas > 9 ? '9+' : naoLidas}
            </Badge>
          )}
        </Button>
      </PopoverTrigger>

      <PopoverContent align="end" className="w-96 p-0">
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
        {/* Cabeçalho */}
        <div className="flex items-center justify-between border-b p-3">
          <span className="text-sm font-medium">Notificações</span>
          {naoLidas > 0 && (
            <Button
              variant="ghost"
              size="sm"
              onClick={handleMarcarTodasLidas}
              className="h-7 text-xs"
            >
=======
        <div className="flex items-center justify-between border-b p-3">
          <span className="text-sm font-medium">Notificações</span>
          {naoLidas > 0 && (
            <Button variant="ghost" size="sm" onClick={handleMarcarTodasLidas} className="h-7 text-xs">
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
              <CheckCheck className="mr-1 size-3" />
              Marcar todas
            </Button>
          )}
        </div>

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
        {/* Lista */}
        <div className="max-h-96 overflow-y-auto">
          {notificacoes.length === 0 ? (
            <p className="p-4 text-center text-sm text-muted-foreground">
              Sem notificações.
            </p>
=======
        <div className="max-h-96 overflow-y-auto">
          {notificacoes.length === 0 ? (
            <p className="p-4 text-center text-sm text-muted-foreground">Sem notificações.</p>
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
          ) : (
            notificacoes.map((n) => (
              <button
                key={n.id}
                type="button"
                onClick={() => handleClickNotificacao(n)}
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                className={`w-full border-b p-3 text-left last:border-0 hover:bg-muted/50 transition-colors ${
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

                {/* 🔥 Estatísticas do prazo expirado (diretor) */}
=======
                className={`w-full border-b p-3 text-left last:border-0 hover:bg-muted/50 transition-colors ${n.lida ? 'opacity-60' : ''}`}
              >
                <div className="flex items-start justify-between gap-2">
                  <p className="text-sm font-medium">{n.titulo}</p>
                  {!n.lida && <span className="mt-1 size-2 shrink-0 rounded-full bg-destructive" />}
                </div>
                <p className="text-xs text-muted-foreground">{n.mensagem}</p>

>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                {n.tipo === 'prazo_expirado' && n.stats && (
                  <div className="mt-2 grid grid-cols-2 gap-x-2 gap-y-0.5 text-xs">
                    <span className="text-muted-foreground">Total:</span>
                    <span className="font-medium">{n.stats.total}</span>
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                    <span className="text-muted-foreground">✅ Submeteram:</span>
                    <span className="font-medium text-green-600">{n.stats.submeteram}</span>
                    <span className="text-muted-foreground">❌ Não submeteram:</span>
                    <span className="font-medium text-red-600">{n.stats.nao_submeteram}</span>
                    <span className="text-muted-foreground">📄 Justificaram:</span>
=======
                    <span className="text-muted-foreground">Submeteram:</span>
                    <span className="font-medium text-green-600">{n.stats.submeteram}</span>
                    <span className="text-muted-foreground">Não submeteram:</span>
                    <span className="font-medium text-red-600">{n.stats.nao_submeteram}</span>
                    <span className="text-muted-foreground">Justificaram:</span>
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                    <span className="font-medium text-blue-600">{n.stats.justificaram}</span>
                  </div>
                )}

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                {/* 🔥 Motivo da justificativa (diretor) */}
=======
                {n.tipo === 'submissao_avaliada' && n.parecer_diretor && (
                  <div className="mt-2 p-2 bg-muted/40 rounded border border-border text-xs">
                    <p className="font-medium text-muted-foreground">Parecer do diretor:</p>
                    <p className="mt-0.5">{n.parecer_diretor}</p>
                  </div>
                )}

                {n.tipo === 'nova_submissao' && (
                  <div className="mt-2 space-y-0.5 text-[11px] text-muted-foreground">
                    <p><strong>{n.professor_nome}</strong></p>
                    <p>{n.disciplina || 'Todas'}{n.classe && ` • 🎓 ${n.classe}`}</p>
                    <p>{n.turma} • v{n.versao}</p>
                  </div>
                )}

>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                {n.tipo === 'justificativa_enviada' && n.motivo && (
                  <div className="mt-2 p-2 bg-muted/40 rounded border border-border text-xs">
                    <p className="font-medium text-muted-foreground">Motivo:</p>
                    <p className="mt-0.5">{n.motivo}</p>
                  </div>
                )}

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                {/* 🔥 Parecer do diretor (professor) */}
=======
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                {n.tipo === 'justificativa_avaliada' && n.parecer_diretor && (
                  <div className="mt-2 p-2 bg-muted/40 rounded border border-border text-xs">
                    <p className="font-medium text-muted-foreground">Parecer do diretor:</p>
                    <p className="mt-0.5">{n.parecer_diretor}</p>
                  </div>
                )}

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                {/* Propinas em atraso */}
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
=======
                {n.tipo === 'propina_atraso' && n.meses?.length > 0 && (
                  <div className="mt-2 flex flex-wrap gap-1">
                    {n.meses.map((mes, i) => (
                      <Badge key={i} variant="destructive" className="font-normal">{mes}</Badge>
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                    ))}
                  </div>
                )}

<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
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

<<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
                <p className="mt-1 text-[10px] text-muted-foreground">
                  {n.criada_em}
                </p>
              </div>
========
                {/* Data + link */}
=======
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
                <div className="mt-1 flex items-center justify-between">
                  <p className="text-[10px] text-muted-foreground">{n.criada_em}</p>
                  {n.url && <ExternalLink className="size-3 text-muted-foreground" />}
                </div>
              </button>
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
>>>>>>>> Modulo-propinas:resources/js/pages/dashboards/aluno/components/notificacoes-sino.jsx
=======
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
            ))
          )}
        </div>
      </PopoverContent>
    </Popover>
  );
<<<<<<< HEAD:resources/js/pages/tenant/dashboards/aluno/components/notificacoes-sino.jsx
}
=======
}
>>>>>>> Modulo-propinas:resources/js/pages/dashboards/professor/components/notificacoes-sino.jsx
