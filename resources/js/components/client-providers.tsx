import { ClientToaster } from '@/components/client-toaster';
import { TooltipProvider } from '@/components/ui/tooltip';
import { useFlashToast } from '@/hooks/use-flash-toast';

export function ClientProviders({ children }: { children: React.ReactNode }) {
  useFlashToast();

  return (
    <TooltipProvider delayDuration={0}>
      {children}
      <ClientToaster />
    </TooltipProvider>
  );
}
