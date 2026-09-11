import { lazy, Suspense, useEffect, useState } from 'react';

// Carrega os componentes apenas no cliente
const TooltipProvider = lazy(() =>
  import('@/components/ui/tooltip').then((m) => ({ default: m.TooltipProvider }))
);

const ClientToaster = lazy(() =>
  import('@/components/client-toaster').then((m) => ({ default: m.ClientToaster }))
);

export function ClientProviders({ children }: { children: React.ReactNode }) {
  const [mounted, setMounted] = useState(false);

  useEffect(() => {
    setMounted(true);
  }, []);

  // No SSR, renderiza apenas os filhos (sem providers)
  if (!mounted) {
    return <>{children}</>;
  }

  // No cliente, carrega os providers com lazy
  return (
    <Suspense fallback={<>{children}</>}>
      <TooltipProvider delayDuration={0}>
        {children}
        <ClientToaster />
      </TooltipProvider>
    </Suspense>
  );
}