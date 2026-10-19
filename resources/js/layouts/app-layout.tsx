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
  return (
    <AppLayoutTemplate breadcrumbs={breadcrumbs}>
      {children}
      <AppDialog />
      <AppDrawer />
    </AppLayoutTemplate>
  );
}
