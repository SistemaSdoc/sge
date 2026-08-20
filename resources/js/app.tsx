import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PortaLayout from './layouts/portal-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function AppProviders({ children }: { children: React.ReactNode }) {
  useFlashToast();

  return (
    <TooltipProvider delayDuration={0}>
      {children}
      <Toaster />
    </TooltipProvider>
  );
}

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  layout: (name) => {
    switch (true) {
      case name === 'welcome/index' || name === 'certificado/show':
        return null;
      case name.startsWith('central/auth/') || name.startsWith('tenant/auth/'):
        return AuthLayout;
      case name.startsWith('settings/'):
        return [AppLayout, SettingsLayout];
      case name.startsWith('portal/'):
        return [PortaLayout];
      default:
        return AppLayout;
    }
  },
  strictMode: true,
  withApp(app) {
    return <AppProviders>{app}</AppProviders>;
  },
  progress: {
    color: '#F8941F',
  },
});

// This will set light / dark mode on load...
initializeTheme();
