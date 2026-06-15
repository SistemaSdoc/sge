import { AppContent } from '@/components/app-content';
import { AppHeader } from '@/components/app-header';
import { AppShell } from '@/components/app-shell';

export default function PortaLayout({
  children,
}: {
  children: React.ReactNode;
}) {
  return (
    <AppShell variant="header">
      <AppHeader breadcrumbs={[]} />
      <AppContent variant="sidebar">
        <div className="container max-w-7xl mx-auto py-6 border">{children}</div>
      </AppContent>
    </AppShell>
  );
}
