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
import { ArrowUpLeft } from 'lucide-react';
import { show as showInstituicao } from '@/actions/App/Http/Controllers/InstituicaoController';
import { show as showClasse } from '@/actions/App/Http/Controllers/CursoClasseController';
import { edit } from '@/actions/App/Http/Controllers/CursoTuteladoController';
import { cn } from '@/lib/utils';

export function Header({ can, params }) {
  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0 space-y-1">
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbLink asChild>
                    {can.instituicao.view ? (
                      <Link
                        className="text-sm font-semibold text-primary hover:text-primary/80"
                        href={
                          showInstituicao({
                            instituicao: params.instituicao.id,
                          }).url
                        }
                      >
                        <span className="line-clamp-1">
                          {params.instituicao.nome}
                        </span>
                      </Link>
                    ) : (
                      <span className="line-clamp-1 text-sm font-semibold text-primary">
                        {params.instituicao.nome}
                      </span>
                    )}
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem>
                  <BreadcrumbPage className="line-clamp-1 text-sm font-semibold text-secondary">
                    {params.cursoTutelado.curso.nome}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription>
              Curso de{' '}
              <span className="font-bold">
                {params.cursoTutelado.curso.nome}
              </span>{' '}
              do <span className="font-bold">{params.instituicao.nome}</span>
            </CardDescription>
          </div>

          {(can.instituicao?.view || params.cursoTutelado.can?.update) && (
            <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
              {can.instituicao?.view && (
                <Button
                  variant="outline"
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={() =>
                    router.visit(
                      showInstituicao({ instituicao: params.instituicao.id })
                        .url,
                    )
                  }
                >
                  <ArrowUpLeft className="shrink-0" />
                  Voltar a instituição
                </Button>
              )}
              {params.cursoTutelado.can?.update && (
                <Button
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={(e) => {
                    e.stopPropagation();
                    router.visit(edit({ ...params }).url);
                  }}
                >
                  Editar Curso
                </Button>
              )}
            </div>
          )}
        </div>
      </CardHeader>

      {/* Cards de Classes */}
      <div className="overflow-hidden">
        {params.cursoTutelado.classes?.length > 0 ? (
          <div
            className="-mb-px grid"
            style={{
              gridTemplateColumns: `repeat(auto-fit, minmax(min(100%, 150px), 1fr))`,
            }}
          >
            {params.cursoTutelado.classes.map((c, index) => {
              const totalItems = params.cursoTutelado.classes.length;
              const isLastItem = index === totalItems - 1;

              return (
                <Link
                  key={c.id}
                  href={showClasse({ ...params, cursoClasse: c.id }).url}
                >
                  <div
                    className={cn(
                      'cursor-pointer bg-card px-3 py-3 text-card-foreground transition-colors hover:bg-accent hover:text-secondary active:bg-accent sm:px-4 sm:py-4',
                      !isLastItem && 'border-r border-b border-foreground/10',
                      isLastItem && 'border-b border-foreground/10',
                    )}
                  >
                    <h3 className="mb-0.5 text-xs font-medium sm:mb-1 sm:text-sm">
                      {c.nome}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                      Clique aqui para ver
                    </p>
                  </div>
                </Link>
              );
            })}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center bg-card p-6 py-4 text-center text-xs/relaxed text-card-foreground">
            <Minus size={20} className="mb-2 text-muted-foreground" />
            <p className="text-sm text-muted-foreground">
              Sem classes definidas
            </p>
          </div>
        )}
      </div>
    </Card>
  );
}
