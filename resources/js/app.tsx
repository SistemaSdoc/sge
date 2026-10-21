import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { useEffect, useState } from 'react';
import { ClientToaster } from '@/components/client-toaster';
import { ErrorBoundary } from '@/components/error-boundary';
import { TooltipProvider } from '@/components/ui/tooltip';
import { initializeTheme } from '@/hooks/use-appearance';
import { useFlashToast } from '@/hooks/use-flash-toast';
import AppLayout from '@/layouts/app-layout';
import AuthLayout from '@/layouts/auth-layout';
import SettingsLayout from '@/layouts/settings/layout';
import UserProfileLayout from '@/layouts/user-profile/layout';

configureEcho({
  broadcaster: 'reverb',
  key: import.meta.env.VITE_REVERB_APP_KEY,
  wsHost: import.meta.env.VITE_REVERB_HOST,
  wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
  forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'http') === 'https',
  enabledTransports: ['ws', 'wss'],
});

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
    <TooltipProvider delayDuration={0}>
      {children}
      <ClientOnly>
        <ClientToaster />
      </ClientOnly>
    </TooltipProvider>
  );
}

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),

  // ✅ Resolve .tsx primeiro, depois .jsx (coexistem no projeto)
  resolve: (name) =>
    resolvePageComponent(
      [`./pages/${name}.tsx`, `./pages/${name}.jsx`],
      import.meta.glob('./pages/**/*.{jsx,tsx}'),
    ),

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

      case name === 'tenant/users/profile/show' ||
        name === 'tenant/users/profile/academic' ||
        name === 'tenant/users/profile/security':
        return [AppLayout, UserProfileLayout];

      case name.startsWith('tenant/settings/'):
        return [AppLayout, SettingsLayout];

      default:
        return AppLayout;
    }
  },

  strictMode: true,

  // ✅ ErrorBoundary global — apanha erros de qualquer página/layout
  withApp(app) {
    return (
      <ErrorBoundary>
        <AppProviders>{app}</AppProviders>
      </ErrorBoundary>
    );
  },

  progress: {
    color: '#F8941F',
  },
});

initializeTheme();