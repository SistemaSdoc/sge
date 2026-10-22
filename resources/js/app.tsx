import { createInertiaApp } from '@inertiajs/react';
import { configureEcho } from '@laravel/echo-react';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { useEffect, useState } from 'react';
import { ClientToaster } from '@/components/client-toaster';
import { ErrorBoundary } from '@/components/error-boundary';
import { TooltipProvider } from '@/components/ui/tooltip';
import { ClientProviders } from '@/components/client-providers';
import { initializeTheme } from '@/hooks/use-appearance';
import { resolvePageLayout } from '@/layouts/resolve-page-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  layout: resolvePageLayout,
  strictMode: true,

  // ✅ ErrorBoundary global — apanha erros de qualquer página/layout
  withApp(app) {
    return <ClientProviders>{app}</ClientProviders>;
  },

  progress: {
    color: '#F8941F',
  },
});

initializeTheme();
