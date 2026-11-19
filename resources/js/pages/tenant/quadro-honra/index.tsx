import { Head } from '@inertiajs/react';
import { FiltrosQuadrosHonra } from '@/components/tenant/quadro-honra/filtros-quadros-honra';
import { FormCriteriosQuadroHonra } from '@/components/tenant/quadro-honra/form-criterios-quadro-honra';
import { ListaQuadrosHonra } from '@/components/tenant/quadro-honra/lista-quadros-honra';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Card, CardDescription, CardHeader } from '@/components/ui/card';
import type {
  FiltroQuadrosHonra,
  Paginacao,
  QuadroHonraResumo,
} from '@/types/quadro-honra';

type QuadroHonraIndexProps = {
  quadros: Paginacao<QuadroHonraResumo>;
  criterios: {
    media_minima: number;
    limite_alunos: number;
  };
  filtros: FiltroQuadrosHonra;
  can: {
    ajustarCriterios: boolean;
  };
  userRole: 'aluno' | 'staff';
};

/** Página de consulta pública à instituição e configuração restrita à Direcção. */
export default function QuadroHonraIndex({
  quadros,
  criterios,
  filtros,
  can,
  userRole,
}: QuadroHonraIndexProps) {
  const isAluno = userRole === 'aluno';

  return (
    <>
      <Head title="Quadro de Honra" />

      <main className="mx-auto flex w-full max-w-7xl flex-col gap-4 p-4 sm:p-6">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b border-foreground/10">
            <Breadcrumb>
              <BreadcrumbList className="flex-wrap">
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbLink asChild>
                    <span className="line-clamp-2 text-sm font-semibold text-primary">
                      {isAluno ? 'Aluno' : 'Gestão'}
                    </span>
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                    Quadro de Honra
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>
            <CardDescription>
              {isAluno
                ? 'Consulte o Quadro de Honra.'
                : 'Os quadros de honra são actualizados automaticamente após o fecho das pautas de cada trimestre.'}
            </CardDescription>
          </CardHeader>
        </Card>

        {can.ajustarCriterios && (
          <FormCriteriosQuadroHonra criterios={criterios} />
        )}

        <FiltrosQuadrosHonra filtros={filtros} />
        <ListaQuadrosHonra quadros={quadros} filtros={filtros} />
      </main>
    </>
  );
}
