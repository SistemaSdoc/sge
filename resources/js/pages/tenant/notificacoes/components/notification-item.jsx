import { Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { NotificacaoItem } from '@/components/notificacao-item';
import {
  markAsRead,
  show,
} from '@/actions/App/Http/Controllers/Tenant/NotificacaoController';

export function NotificationItem({ notificacao }) {
  const dados = notificacao.dados ?? notificacao.data ?? {};
  const lida = notificacao.lida ?? !!notificacao.read_at;

  const handleMarcarLida = () => {
    router.post(markAsRead(notificacao.id).url, {}, { preserveScroll: true });
  };

  return (
    <article className="flex min-w-0 flex-col gap-3 py-4 sm:flex-row sm:items-start sm:justify-between">
      <div className="min-w-0 flex-1">
        <NotificacaoItem
          notificacao={{ ...notificacao, data: dados, lida }}
          variant="compact"
          onClick={() => {}}
        />
      </div>

      <div className="flex shrink-0 flex-wrap items-center gap-x-2 gap-y-1">
        {!lida && (
          <Button
            type="button"
            variant="outline"
            size="xs"
            className="hover:cursor-pointer"
            onClick={handleMarcarLida}
          >
            Marcar como lida
          </Button>
        )}

        <Button
          asChild
          variant="outline"
          size="xs"
          className="hover:cursor-pointer"
        >
          <Link href={show(notificacao.id).url}>Ver detalhes</Link>
        </Button>
      </div>
    </article>
  );
}
