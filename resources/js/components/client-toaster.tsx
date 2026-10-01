import { lazy, Suspense } from 'react';

const Toaster = lazy(() =>
  import('@/components/ui/sonner').then((m) => ({ default: m.Toaster }))
);

export function ClientToaster() {
  return (
    <Suspense fallback={null}>
      <Toaster />
    </Suspense>
  );
}