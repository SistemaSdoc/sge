import { Spinner } from '@/components/spinner';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { LoaderCircle } from 'lucide-react';
import { useEffect } from 'react';

export default function Page() {
  useEffect(() => {
    let checking = false;

    const checkTenantStatus = async () => {
      if (checking) {
        return;
      }

      checking = true;

      try {
        const response = await fetch(window.location.href, {
          cache: 'no-store',
          credentials: 'same-origin',
          headers: {
            Accept: 'text/html',
          },
        });

        if (response.headers.get('X-Tenant-Status') !== 'provisioning') {
          window.location.reload();
        }
      } finally {
        checking = false;
      }
    };

    const statusTimer = window.setInterval(checkTenantStatus, 5000);

    return () => window.clearInterval(statusTimer);
  }, []);

  return (
    <section className="mx-auto flex min-h-screen w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
      <Empty className="gap-6 p-0">
        <EmptyHeader className="max-w-xl gap-4">
          <EmptyMedia variant="icon">
            <Spinner />
          </EmptyMedia>
          <EmptyTitle>Configurando o seu espaço</EmptyTitle>
          <EmptyDescription>
            O seu espaço está a ser preparado. Aguarde alguns instantes e tente
            novamente.
          </EmptyDescription>
        </EmptyHeader>
      </Empty>
    </section>
  );
}
