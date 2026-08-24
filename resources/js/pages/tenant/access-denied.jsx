import { Head, Link, router } from '@inertiajs/react';
import { Lock } from 'lucide-react';
import { ArrowLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { logout } from '@/routes/tenant';
import {
  Empty,
  EmptyContent,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { StatusBadge } from '@/pages/central/tenants/components/status-badge';

const STATUS_MESSAGES = {
  suspended: {
    title: 'Acesso Suspenso',
    description:
      'A sua instituição foi temporariamente suspensa. Contacte o suporte para mais informações.',
    action: 'Contactar Suporte',
  },
  inactive: {
    title: 'Instituição Inactiva',
    description:
      'A sua instituição não está activa no momento. Verifique o seu estatuto ou renove a sua subscrição.',
    action: 'Verificar Estatuto',
  },
  archived: {
    title: 'Instituição Arquivada',
    description:
      'A sua instituição foi arquivada e não está disponível para acesso. Entre em contacto com o administrador.',
    action: 'Contactar Administrador',
  },
  pending: {
    title: 'Verificação Pendente',
    description:
      'A sua instituição ainda está em processo de verificação. Este processo pode levar até 24 horas.',
    action: 'Saber Mais',
  },
};

export default function AccessDenied({ status }) {
  const messageConfig = STATUS_MESSAGES[status] || STATUS_MESSAGES.inactive;

  const handleLogout = () => {
    router.flushAll();
  };

  return (
    <div className="flex flex-col items-center justify-center gap-3 px-4 py-16 text-center sm:gap-4 sm:p-6 sm:py-24">
      <Head title="Acesso Negado" />
      <Empty>
        <EmptyHeader>
          <EmptyMedia variant="icon">
            <Lock className="text-destructive" />
          </EmptyMedia>
          <EmptyTitle>{messageConfig.title}</EmptyTitle>
          <div className="mb-2 flex justify-center">
            <StatusBadge status={status} />
          </div>
          <EmptyDescription>{messageConfig.description}</EmptyDescription>
        </EmptyHeader>
        <EmptyContent>
          <EmptyDescription>
            <div className="flex flex-col gap-2 sm:flex-row sm:justify-center">
              <Button variant="outline" className="group" asChild>
                <Link
                  href={logout()}
                  method="post"
                  as="button"
                  onClick={handleLogout}
                >
                  <ArrowLeft className="size-4 transition-all duration-150 group-hover:-translate-x-1" />
                  Terminar sessão
                </Link>
              </Button>

              <Button variant="default">
                <a href="mailto:suporte@ludus.ao">{messageConfig.action}</a>
              </Button>
            </div>
          </EmptyDescription>
        </EmptyContent>
      </Empty>
    </div>
  );
}
