import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
} from '@/components/ui/card';
import { ArrowUpLeft } from 'lucide-react';
import { show as showCurso } from '@/actions/App/Http/Controllers/CursoTuteladoController';
import { Button } from '@/components/ui/button';
import { Link, router } from '@inertiajs/react';
import { create } from '@/actions/App/Http/Controllers/CursoClasseTurnoController';

export function Header({ can, params, anoLectivoAtualNome, turnos }) {
  return (
    <Card className="mb-0 gap-0 overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0 space-y-1">
            <Breadcrumb>
              <BreadcrumbList>
                <BreadcrumbItem>
                  <BreadcrumbLink asChild>
                    {can.curso.view ? (
                      <Link
                        className="text-sm font-semibold text-primary hover:text-primary/80"
                        href={
                          showCurso({
                            instituicao: params.instituicao.id,
                            cursoTutelado: params.cursoTutelado.id,
                          }).url
                        }
                      >
                        <span className="line-clamp-1">
                          {params.cursoTutelado.nome}
                        </span>
                      </Link>
                    ) : (
                      <span className="line-clamp-1 text-sm font-semibold text-primary">
                        {params.cursoTutelado.nome}
                      </span>
                    )}
                  </BreadcrumbLink>
                </BreadcrumbItem>

                <BreadcrumbSeparator />

                <BreadcrumbItem>
                  <BreadcrumbPage className="line-clamp-1 text-sm font-semibold text-secondary">
                    {params.cursoClasse.nome}
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription>
              Gerir disciplinas e turmas por turno no ano lectivo{' '}
              <span className="font-bold">{anoLectivoAtualNome}</span>
            </CardDescription>
          </div>

          {(can.curso?.view || can.turno?.create) && (
            <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
              {can.curso?.view && (
                <Button
                  variant="outline"
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={() =>
                    router.visit(
                      showCurso({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                      }).url,
                    )
                  }
                >
                  <ArrowUpLeft className="shrink-0" />
                  Voltar ao curso
                </Button>
              )}

              {can.turno.create && turnos.length < 3 && (
                <Button
                  size="sm"
                  className="w-full justify-center sm:w-auto"
                  onClick={() =>
                    router.visit(
                      create({
                        instituicao: params.instituicao.id,
                        cursoTutelado: params.cursoTutelado.id,
                        cursoClasse: params.cursoClasse.id,
                      }).url,
                    )
                  }
                >
                  Adicionar Turno
                </Button>
              )}
            </div>
          )}
        </div>
      </CardHeader>
    </Card>
  );
}
