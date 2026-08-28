import { Bell } from 'lucide-react';
import { Breadcrumbs } from '@/components/breadcrumbs';
import {
  NotificacoesDrawer,
  useNotificacoes,
} from '@/components/notificacoes-drawer';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { useDrawer } from '@/hooks/use-drawer';
import type { BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Separator } from './ui/separator';

export function AppSidebarHeader({
  breadcrumbs = [],
}: {
  breadcrumbs?: BreadcrumbItemType[];
}) {
  const { openForm } = useDrawer();
  const { naoLidas } = useNotificacoes();

  const abrirNotificacoes = () => {
    openForm({
      title: 'Notificações',
      description: 'Consulte as novidades e solicitações da instituição.',
      closeOnOutsideClick: true,
      content: <NotificacoesDrawer />,
    });
  };

  return (
    <header className="sticky top-0 z-20 flex h-16 shrink-0 items-center gap-2 border-b bg-background/95 px-4 backdrop-blur supports-backdrop-filter:bg-background/80">
      <div className="flex h-full items-center gap-2">
        <SidebarTrigger className="-ml-1" />
        <Separator orientation="vertical" className="mx-1 h-full" />
        <Breadcrumbs breadcrumbs={breadcrumbs} />
      </div>
      <div className="ml-auto">
        <Button
          type="button"
          variant="ghost"
          size="icon"
          className="relative"
          onClick={abrirNotificacoes}
          aria-label="Abrir notificações"
        >
          <Bell className="size-5" />
          {naoLidas > 0 && (
            <Badge
              variant="destructive"
              className="absolute -top-1 -right-1 flex size-4 items-center justify-center rounded-full p-0 text-[10px]"
            >
              {naoLidas > 9 ? '9+' : naoLidas}
            </Badge>
          )}
        </Button>
      </div>
    </header>
  );
}
