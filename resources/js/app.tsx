import { createInertiaApp } from '@inertiajs/react';
import { TooltipProvider } from '@/components/ui/tooltip'; // ← import direto (não lazy)
import { ClientToaster } from '@/components/client-toaster';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import PortaLayout from './layouts/portal-layout';
import { useEffect, useState } from 'react';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

function ClientOnly({ children }: { children: React.ReactNode }) {
  const [hasMounted, setHasMounted] = useState(false);
  useEffect(() => {
    setHasMounted(true);
  }, []);
  if (!hasMounted) return null;
  return <>{children}</>;
}

function AppProviders({ children }: { children: React.ReactNode }) {
  useFlashToast();
  return (
    // TooltipProvider agora é renderizado tanto no servidor quanto no cliente
    <TooltipProvider delayDuration={0}>
      {children}
      {/* Apenas o Toaster fica no cliente */}
      <ClientOnly>
        <ClientToaster />
      </ClientOnly>
    </TooltipProvider>
  );
}

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  layout: (name) => {
    switch (true) {
      case name === 'welcome/index' ||
        name === 'teste' ||
        name === 'certificado/show':
        return null;
      case name.startsWith('auth/'):
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

// Inicializa o tema (cliente-only, usa useEffect)
initializeTheme();