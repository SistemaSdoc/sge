import { createInertiaApp } from '@inertiajs/react';
import { ClientProviders } from '@/components/client-providers';
import { initializeTheme } from '@/hooks/use-appearance';
import { resolvePageLayout } from '@/layouts/resolve-page-layout';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
  title: (title) => (title ? `${title} - ${appName}` : appName),
  layout: resolvePageLayout,
  strictMode: true,
  withApp(app) {
    return <ClientProviders>{app}</ClientProviders>;
  },
  progress: {
    color: '#F8941F',
  },
});

initializeTheme();
