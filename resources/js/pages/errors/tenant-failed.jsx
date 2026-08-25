import { Head } from '@inertiajs/react';
import { AlertTriangle, Mail } from 'lucide-react';
import { useEffect } from 'react';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { Button } from '@/components/ui/button';

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

        if (response.headers.get('X-Tenant-Status') !== 'failed') {
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
    <>
      <Head title="Falha na configuração" />

      <section className="mx-auto flex min-h-screen w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
        <Empty className="gap-6 p-0">
          <EmptyHeader className="max-w-xl gap-4">
            <EmptyMedia variant="icon">
              <AlertTriangle aria-hidden="true" />
            </EmptyMedia>
            <EmptyTitle>Não foi possível concluir a configuração</EmptyTitle>
            <EmptyDescription>
              Não foi possível configurar o seu espaço. Contacte o suporte para
              obter ajuda.
            </EmptyDescription>
          </EmptyHeader>

          <EmptyContent className="max-w-md gap-5">
            <Button asChild className="h-11 w-full sm:w-auto">
              <a href="mailto:suporte@stanclay.app">
                <Mail />
                Contactar o suporte
              </a>
            </Button>
          </EmptyContent>
        </Empty>
      </section>
    </>
  );
}
