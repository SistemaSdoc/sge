import { Head, Link } from '@inertiajs/react';
import { ArrowLeft, Clock3 } from 'lucide-react';
import { index } from '@/actions/App/Http/Controllers/Central/TenantController';
import { Button } from '@/components/ui/button';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { useDialog } from '@/hooks/use-dialog';
import { AlterarStatusDialog } from './components/alterar-status-dialog';

export default function Pending({ tenant }) {
  const { openForm, closeDialog } = useDialog();

  const handleActivate = () => {
    openForm({
      title: `Activar tenant - ${tenant.id}`,
      description:
        'Escolha o tipo de activação para configurar esta instituição.',
      size: 'sm',
      content: (
        <AlterarStatusDialog
          tenant={tenant}
          availableTransitions={tenant.availableTransitions || {}}
          onCancel={() => closeDialog()}
          onSuccess={() => closeDialog()}
        />
      ),
    });
  };

  return (
    <>
      <Head title="Tenant pendente" />

      <section className="mx-auto flex min-h-[calc(100vh-65px)] w-full max-w-3xl items-center justify-center px-4 py-10 sm:px-6 sm:py-14">
        <Empty className="gap-6 p-0">
          <EmptyHeader className="max-w-xl gap-4">
            <EmptyMedia variant="icon">
              <Clock3 aria-hidden="true" />
            </EmptyMedia>
            <EmptyTitle>Tenant pendente de activação</EmptyTitle>
            <EmptyDescription>
              Esta instituição ainda não foi configurada. Active o tenant para
              disponibilizar os dados e o acesso aos utilizadores.
            </EmptyDescription>
          </EmptyHeader>

          <div className="flex w-full max-w-xl flex-col gap-2 sm:flex-row sm:justify-center">
            <Button asChild variant="outline" className="w-full sm:w-auto">
              <Link href={index().url}>
                <ArrowLeft />
                Voltar à lista
              </Link>
            </Button>
            <Button onClick={handleActivate} className="w-full sm:w-auto">
              Activar tenant
            </Button>
          </div>
        </Empty>
      </section>
    </>
  );
}
