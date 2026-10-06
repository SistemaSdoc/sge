import { Head, InfiniteScroll, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { marcarTodasLidas } from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';
import { NotificationsList } from './components/notification-list';

export default function Index({ notificacoes, naoLidas = 0 }) {
  const notificacoesDaPagina = notificacoes?.data ?? [];

  const marcarTodas = () => {
    if (naoLidas === 0) {
      return;
    }

    router.post(marcarTodasLidas().url, {}, { preserveScroll: true });
  };

  return (
    <div className="mx-auto w-full max-w-7xl px-3 py-4 sm:px-6 sm:py-6 lg:px-8">
      <Head title="Notificações" />

      <header className="pb-4 sm:pb-5">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="min-w-0">
            <h1 className="text-lg font-semibold tracking-tight text-foreground sm:text-xl">
              Central de notificações
            </h1>

            <p className="mt-1 text-xs leading-relaxed text-muted-foreground sm:text-sm">
              {naoLidas === 0
                ? 'Não há notificações por ler.'
                : `${naoLidas} ${naoLidas === 1 ? 'notificação aguarda' : 'notificações aguardam'} leitura.`}{' '}
            </p>
          </div>

          {naoLidas > 0 && (
            <Button
              type="button"
              variant="default"
              onClick={marcarTodas}
              className="w-full bg-foreground text-background hover:bg-foreground/90 sm:w-auto"
            >
              Marcar todas como lidas
            </Button>
          )}
        </div>
      </header>

      <section className="min-w-0">
        <InfiniteScroll
          data="notificacoes"
          onlyNext
          buffer={500}
          loading={() => (
            <p
              className="px-4 py-3 text-center text-xs text-muted-foreground"
              role="status"
            >
              A carregar mais notificações...
            </p>
          )}
        >
          <NotificationsList notificacoes={notificacoesDaPagina} />
        </InfiniteScroll>
      </section>
    </div>
  );
}
