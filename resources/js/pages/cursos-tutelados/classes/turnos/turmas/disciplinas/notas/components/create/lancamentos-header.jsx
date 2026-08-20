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
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { ArrowUpLeft } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { show as showClasse } from '@/actions/App/Http/Controllers/Tenant/CursoClasseController';
import { show as showCurso } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { show as showTurma } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';

export function Header({ can, turma, params }) {
  const handleBackClick = () => {
    router.visit(
      showTurma({
        instituicao: params.instituicao.id,
        cursoTutelado: params.cursoTutelado.id,
        cursoClasse: params.cursoClasse.id,
        cursoClasseTurno: params.cursoClasseTurno.id,
        turma: params.turma.id,
      }).url,
    );
  };

  return (
    <Card className="gap-0! overflow-visible">
      <CardHeader>
        {/* Breadcrumb Navigation */}
        <CardTitle className="flex items-center gap-1">
          <Breadcrumb>
            <BreadcrumbList>
              {/* Curso */}
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
                      {params.cursoTutelado?.nome}
                    </Link>
                  ) : (
                    <span className="text-sm font-semibold text-primary">
                      {params.cursoTutelado?.nome}
                    </span>
                  )}
                </BreadcrumbLink>
              </BreadcrumbItem>

              <BreadcrumbSeparator />

              {/* Classe */}
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  {can.classe.view ? (
                    <Link
                      className="text-sm font-semibold text-primary hover:text-primary/80"
                      href={
                        showClasse({
                          instituicao: params.instituicao.id,
                          cursoTutelado: params.cursoTutelado.id,
                          cursoClasse: params.cursoClasse.id,
                        }).url
                      }
                    >
                      {params.cursoClasse?.nome}
                    </Link>
                  ) : (
                    <span className="text-sm font-semibold text-primary">
                      {params.cursoClasse?.nome}
                    </span>
                  )}
                </BreadcrumbLink>
              </BreadcrumbItem>

              <BreadcrumbSeparator />

              {/* Turno */}
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  {can.turno.view ? (
                    <Link
                      className="text-sm font-semibold text-primary hover:text-primary/80"
                      href={
                        showClasse({
                          instituicao: params.instituicao.id,
                          cursoTutelado: params.cursoTutelado.id,
                          cursoClasse: params.cursoClasse.id,
                        }).url
                      }
                      data={{ turno: params.cursoClasseTurno.id }}
                    >
                      {params.cursoClasseTurno?.nome}
                    </Link>
                  ) : (
                    <span className="text-sm font-semibold text-primary">
                      {params.cursoClasseTurno?.nome}
                    </span>
                  )}
                </BreadcrumbLink>
              </BreadcrumbItem>

              <BreadcrumbSeparator />

              {/* Turma */}
              <BreadcrumbItem>
                <BreadcrumbLink asChild>
                  {can.turma.view ? (
                    <Link
                      className="text-sm font-semibold text-primary hover:text-primary/80"
                      href={
                        showTurma({
                          instituicao: params.instituicao.id,
                          cursoTutelado: params.cursoTutelado.id,
                          cursoClasse: params.cursoClasse.id,
                          cursoClasseTurno: params.cursoClasseTurno.id,
                          turma: params.turma.id,
                        }).url
                      }
                      data={{ turma: params.turma.id }}
                    >
                      {turma?.nome}
                    </Link>
                  ) : (
                    <span className="text-sm font-semibold text-primary">
                      {turma?.nome}
                    </span>
                  )}
                </BreadcrumbLink>
              </BreadcrumbItem>

              <BreadcrumbSeparator />

              {/* Disciplina */}
              <BreadcrumbItem>
                <BreadcrumbPage className="text-sm font-semibold text-secondary">
                  {params.classeTurnoDisciplina?.nome}
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </CardTitle>

        {/* Description */}
        <CardDescription>
          Gerir alunos e disciplinas desta turma no ano lectivo
        </CardDescription>

        {/* Actions */}
        <CardAction className="flex gap-3">
          {can.turma.view && (
            <Button variant="outline" size="sm" onClick={handleBackClick}>
              <ArrowUpLeft /> Voltar a turma
            </Button>
          )}
        </CardAction>
      </CardHeader>
    </Card>
  );
}
