import { createInertiaApp } from '@inertiajs/react';
import { Toaster } from '@/components/ui/sonner';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';

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
    if (name.startsWith('errors/')) {
      return null;
    }

    switch (true) {
      case name === 'central/welcome/index' ||
        name === 'tenant/certificado/show' ||
        name === 'tenant/access-denied':
        return null;
      case name.startsWith('central/auth/') || name.startsWith('tenant/auth/'):
        return AuthLayout;
      case name.startsWith('tenant/settings/'):
        return [AppLayout, SettingsLayout];
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
