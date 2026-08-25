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
  show,
} from '@/actions/App/Http/Controllers/Central/TenantController';
import { StatusBadge } from '../../../components/status-badge';

export function Header({ can, tenant, title = 'Todas as tabelas' }) {
  return (
    <Card className="gap-0! overflow-visible">
      <CardHeader className="border- border-foreground/10">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0 space-y-1">
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
                  <BreadcrumbLink asChild>
                    <Link
                      className="text-sm font-semibold text-primary hover:text-primary/80"
                      href={show({ tenant: tenant.id }).url}
                    >
                      <span className="line-clamp-1">
                        {tenant.instituicao.nome}
                      </span>
                    </Link>
                  </BreadcrumbLink>
                </BreadcrumbItem>

                <BreadcrumbSeparator />

                <BreadcrumbItem>
                  <BreadcrumbPage className="line-clamp-1 text-sm font-semibold text-secondary">
                    {title}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription>
              Análise detalhada das tabelas da base de dados
            </CardDescription>
          </div>

          <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
            <Button
              variant="outline"
              size="sm"
              className="w-full justify-center sm:w-auto"
              onClick={() => router.visit(show({ tenant: tenant.id }).url)}
            >
              <ArrowUpLeft className="shrink-0" />
              Voltar a página de detalhes
            </Button>
          </div>
        </div>
      </CardHeader>
    </Card>
  );
}
