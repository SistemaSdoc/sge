import { Head } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { useEffect } from 'react';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';

export default function Provisioning({ tenant }) {
  useEffect(() => {
    let checking = false;

    const checkStatus = async () => {
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

    const statusTimer = window.setInterval(checkStatus, 3000);

    return () => window.clearInterval(statusTimer);
  }, []);

  return (
    <>
      <Head title="A configurar" />

      <section className="mx-auto flex min-h-[calc(100vh-65px)] w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
        <Empty className="gap-6 p-0">
          <EmptyHeader className="max-w-xl gap-4">
            <EmptyMedia variant="icon">
              <LoaderCircle className="animate-spin" aria-hidden="true" />
            </EmptyMedia>
            <EmptyTitle>Tenant em configuração</EmptyTitle>
            <EmptyDescription>
              {tenant?.id
                ? `${tenant.id} está a ser preparado.`
                : 'O tenant está a ser preparado.'}{' '}
              Esta página será actualizada automaticamente quando terminar.
            </EmptyDescription>
          </EmptyHeader>
        </Empty>
      </section>
    </>
  );
}
