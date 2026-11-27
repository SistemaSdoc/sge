import { Head, Link } from '@inertiajs/react';
import {
  ArrowUpLeft,
  CircleAlert,
  CircleSlash,
  Home,
  LockIcon,
  LockKeyhole,
  RefreshCw,
  ServerCrash,
} from 'lucide-react';
import { Button } from '@/components/ui/button';

const errorContent = {
  403: {
    icon: LockIcon,
    eyebrow: 'Acesso restrito',
    title: 'Não pode aceder a esta página',
    description:
      'A sua conta não tem permissão para consultar este recurso. Se acredita que isto é um engano, entre em contacto com o administrador.',
    action: 'Voltar a página inicial',
    tone: 'text-red-500',
  },
  404: {
    icon: CircleSlash,
    eyebrow: 'Página não encontrada',
    title: 'Este caminho não existe',
    description:
      'A página pode ter sido movida, removida ou o endereço pode estar incorreto.',
    action: 'Ir para o início',
    tone: 'text-blue-500',
  },
  500: {
    icon: ServerCrash,
    eyebrow: 'Erro inesperado',
    title: 'Algo correu mal',
    description:
      'Encontrámos um problema ao processar o seu pedido. Tente novamente dentro de instantes.',
    action: 'Tentar novamente',
    tone: 'text-orange-500',
  },
  503: {
    icon: RefreshCw,
    eyebrow: 'Serviço temporariamente indisponível',
    title: 'Voltamos já',
    description:
      'Estamos a fazer alguns ajustes no sistema. Tente novamente dentro de instantes.',
    action: 'Tentar novamente',
    tone: 'text-amber-500',
  },
};

export default function ErrorPage({ status }) {
  const content = errorContent[status] || errorContent[500];
  const Icon = content.icon;
  const canRetry = status === 500 || status === 503;

  return (
    <>
      <Head title={`${status} - ${content.eyebrow}`} />

      <main className="relative flex min-h-screen items-center justify-center overflow-hidden bg-muted/30 px-4 py-12 sm:px-6">
        <div className="pointer-events-none absolute inset-0" />

        <section className="relative w-full max-w-xl text-center">
          <div className="mx-auto mb-8 flex size-12 items-center justify-center border border-border">
            <Icon aria-hidden="true" className={`size-7 ${content.tone}`} />
          </div>

          <p className="mb-3 text-sm font-semibold tracking-[0.18em] text-muted-foreground uppercase">
            {content.eyebrow}
          </p>
          <p className="font-mono text-7xl leading-none font-bold tracking-tight text-foreground sm:text-8xl">
            {status}
          </p>
          <h1 className="mt-6 text-2xl font-semibold tracking-tight text-foreground sm:text-3xl">
            {content.title}
          </h1>
          <p className="mx-auto mt-4 max-w-md text-sm leading-6 text-muted-foreground sm:text-base">
            {content.description}
          </p>

          <div className="mt-8 flex flex-col justify-center gap-3 sm:flex-row">
            <Button asChild>
              <Link href="/">
                <ArrowUpLeft />
                Voltar à página inicial
              </Link>
            </Button>

            {canRetry && (
              <Button
                variant="outline"
                onClick={() => window.location.reload()}
              >
                <RefreshCw />
                Recarregar
              </Button>
            )}
          </div>

          <div className="mt-10 flex items-center justify-center gap-2 text-xs text-muted-foreground">
            <CircleAlert className="size-3.5" />
            <span>Erro {status}</span>
          </div>
        </section>
      </main>
    </>
  );
}
