import { Head, Link, router } from '@inertiajs/react';
import { AlertTriangle, ArrowLeft, RotateCcw } from 'lucide-react';
import {
  index,
  toggleStatus,
} from '@/actions/App/Http/Controllers/Central/TenantController';
import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';

export default function ProvisioningFailed({ tenant }) {
  const canRetry = ['active', 'trial'].includes(tenant.target_status);

  const handleRetry = () => {
    if (!canRetry) {
      return;
    }

    router.post(
      toggleStatus(tenant.id).url,
      { status: tenant.target_status },
      { preserveScroll: true },
    );
  };

  return (
    <>
      <Head title="Falha na configuração" />

      <section className="mx-auto flex min-h-[calc(100vh-65px)] w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
        <Empty className="gap-6 p-0">
          <EmptyHeader className="max-w-xl gap-4">
            <EmptyMedia variant="icon">
              <AlertTriangle aria-hidden="true" />
            </EmptyMedia>
            <EmptyTitle>Não foi possível configurar o tenant</EmptyTitle>
            <EmptyDescription>
              A configuração falhou após {tenant.attempts} tentativa(s). O
              tenant não foi disponibilizado aos utilizadores.
            </EmptyDescription>
          </EmptyHeader>

          <div className="w-full max-w-xl border border-destructive/30 bg-destructive/5 p-4 text-left">
            <p className="text-sm font-medium">Detalhes técnicos</p>
            <pre className="mt-2 max-h-48 overflow-auto text-xs whitespace-pre-wrap text-muted-foreground">
              {tenant.error || 'Não foi registado nenhum detalhe adicional.'}
            </pre>
          </div>

          <div className="flex w-full max-w-xl flex-col gap-2 sm:flex-row sm:justify-center">
            <Button asChild variant="outline" className="w-full sm:w-auto">
              <Link href={index().url}>
                <ArrowLeft />
                Voltar à lista
              </Link>
            </Button>
            <Button
              onClick={handleRetry}
              disabled={!canRetry}
              className="w-full sm:w-auto"
            >
              <RotateCcw />
              Tentar novamente
            </Button>
          </div>
        </Empty>
      </section>
    </>
  );
}
