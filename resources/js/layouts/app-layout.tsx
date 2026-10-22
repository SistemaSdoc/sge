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