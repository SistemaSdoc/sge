import { usePage } from '@inertiajs/react';
import { useEchoNotification } from '@laravel/echo-react';
import { AppDialog } from '@/components/app-dialog';
import { AppDrawer } from '@/components/app-drawer';
import { ErrorBoundary } from '@/components/error-boundary';
import AppLayoutTemplate from '@/layouts/app/app-sidebar-layout';
import type { BreadcrumbItem } from '@/types';

export default function AppLayout({
  breadcrumbs = [],
  children,
}: {
  breadcrumbs?: BreadcrumbItem[];
  children: React.ReactNode;
}) {
  const { auth } = usePage().props;
  const userId = auth?.user?.id;

  // ✅ Só subscreve se houver user — evita canal "undefined"
  useEchoNotification(
    userId ? `App.Models.Tenant.User.${userId}` : '',
    (notification) => {
      //  REMOVIDO o alert() que bloqueava o browser
      console.log('Nova notificação recebida via Echo:', notification);

      // Se quiseres mostrar visualmente, usa o toast (não bloqueia):
      // toast.info('Nova notificação recebida');
    },
  );

  return (
    <ErrorBoundary>
      <AppLayoutTemplate breadcrumbs={breadcrumbs}>
        {children}
        <AppDialog />
        <AppDrawer />
      </AppLayoutTemplate>
    </ErrorBoundary>
  );
}