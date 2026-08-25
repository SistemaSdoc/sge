import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader } from '@/components/ui/card';
import { Link, router } from '@inertiajs/react';
import { ArrowUpLeft, ArrowUpRight } from 'lucide-react';
import {
  index,
  edit,
} from '@/actions/App/Http/Controllers/Central/TenantController';
import { StatusBadge } from './status-badge';

export function Header({ can, tenant }) {
  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <div className="flex flex-col gap-4 md:flex-row md:items-start md:justify-between md:gap-3">
          <div className="min-w-0 flex-1 space-y-1">
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbLink asChild>
                    <Link
                      className="text-sm font-semibold text-primary hover:text-primary/80"
                      href={index().url}
                    >
                      <span className="line-clamp-1">Instituições</span>
                    </Link>
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                  <BreadcrumbPage className="line-clamp-1 text-sm font-semibold text-secondary">
                    {tenant.instituicao?.nome ?? tenant.id}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription></CardDescription>
          </div>

          <div className="flex w-full shrink-0 flex-col gap-2 sm:flex-row md:w-auto">
            <Button
              variant="outline"
              size="sm"
              className="w-full sm:w-auto"
              onClick={() => router.visit(index().url)}
            >
              <ArrowUpLeft className="shrink-0" />
              Voltar a lista
            </Button>

            <Button
              size="sm"
              className="w-full sm:w-auto"
              onClick={(e) => {
                e.stopPropagation();
                router.visit(edit({ tenant: tenant.id }).url);
              }}
            >
              Editar Instituição
            </Button>
          </div>
        </div>
      </CardHeader>

      {/* Cards de métricas */}
      <div className="grid grid-cols-1 divide-y bg-muted/20 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
        {/* Admin info */}
        <div className="relative px-4 py-3 sm:py-4">
          <div className="absolute top-2 right-2">
            <span className="inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
              <span className="relative flex size-1.5">
                <span className="absolute inline-flex h-full w-full animate-ping bg-emerald-500 opacity-75" />
                <span className="relative inline-flex size-1.5 bg-emerald-500" />
              </span>
              Diretor
            </span>
          </div>
          <span className="block text-sm font-medium">
            {tenant?.instituicao?.user?.nome ?? 'Instituição em configuração'}
          </span>
          <p className="mt-1 flex text-xs text-muted-foreground">
            <a
              href={`mailto:${tenant?.instituicao?.user?.email ?? ''}`}
              className="transition-colors hover:text-foreground"
            >
              {tenant?.instituicao?.user?.email ??
                'Dados ainda não disponíveis'}
            </a>
            <ArrowUpRight className="ml-1 h-3 w-3 sm:h-4 sm:w-4" />
          </p>
        </div>

        {/* Domínio */}
        <div className="flex flex-col items-center justify-center px-4 py-3 text-center sm:py-4">
          <Button
            asChild
            variant={'link'}
            className="h-auto p-0 text-xs font-medium sm:text-sm"
          >
            <Link
              href={`http://${tenant.domain}`}
              target="_blank"
              className="line-clamp-1"
            >
              http://{tenant?.domain}{' '}
              <ArrowUpRight className="ml-1 h-3 w-3 sm:h-4 sm:w-4" />
            </Link>
          </Button>
          <p className="mt-1 text-xs text-muted-foreground">Domínio</p>
        </div>

        {/* Status */}
        <div className="flex flex-col items-center justify-center px-4 py-3 text-center sm:py-4">
          <p className="text-xs font-bold sm:text-sm">
            <StatusBadge status={tenant.status} variant="badge" />
          </p>
          <p className="mt-1 text-xs text-muted-foreground">Status</p>
        </div>
      </div>
    </Card>
  );
}
