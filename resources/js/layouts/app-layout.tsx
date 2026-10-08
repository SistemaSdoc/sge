import { usePage } from '@inertiajs/react';
import { useEchoNotification } from '@laravel/echo-react';
import { AppDialog } from '@/components/app-dialog';
import { AppDrawer } from '@/components/app-drawer';
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

  useEchoNotification(
    `App.Models.Tenant.User.${auth.user?.id}`,
    (notification) => {
      alert(JSON.stringify(notification));
      console.log('Nova notificação recebida via Echo:', notification);
    },
  );

  return (
    <AppLayoutTemplate breadcrumbs={breadcrumbs}>
      {children}
      <AppDialog />
      <AppDrawer />
    </AppLayoutTemplate>
  );
}
