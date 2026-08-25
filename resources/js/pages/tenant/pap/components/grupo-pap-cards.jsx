import { router, Link } from '@inertiajs/react';
import { show } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowUpRightIcon, Users2 } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

export function GrupoPapCards({ grupos = [] }) {
  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      {grupos.length > 0 ? (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {grupos.map((grupo) => (
            <Card
              key={grupo.id}
              className="flex flex-col gap-5 hover:cursor-pointer"
              onClick={() =>
                router.visit(
                  show({
                    instituicao: grupo.instituicao.id,
                    cursoTutelado: grupo.cursoTutelado.id,
                    cursoClasse: grupo.cursoClasse.id,
                    cursoClasseTurno: grupo.cursoClasseTurno.id,
                    turma: grupo.turma.id,
                    grupoPap: grupo.id,
                  }).url,
                )
              }
            >
              <CardHeader>
                <CardTitle>{grupo.nome_grupo}</CardTitle>
                <CardDescription>{grupo.tema_grupo}</CardDescription>
              </CardHeader>

              <CardContent className="flex-1 space-y-3">
                <div className="space-y-2">
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <span>Elementos</span>
                  </div>
                  <div className="flex flex-wrap gap-1">
                    {grupo.elementos?.length > 0 ? (
                      grupo.elementos.map((elemento) => (
                        <Badge
                          key={elemento.id}
                          variant="secondary"
                          asChild
                          onClick={(e) => e.stopPropagation()}
                          className="hover:underline"
                        >
                          <Link href={`/dashboard/alunos/${elemento.id}`}>
                            {elemento.nome?.split(' ').slice(0, 2).join(' ')}{' '}
                            <ArrowUpRightIcon size={10} />
                          </Link>
                        </Badge>
                      ))
                    ) : (
                      <span className="text-xs text-muted-foreground">
                        Sem elementos
                      </span>
                    )}
                  </div>
                </div>

                <div className="grid grid-cols-2 gap-3 pt-3 text-sm">
                  <div className="space-y-1">
                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                      <span>Tutor</span>
                    </div>
                    <p className="truncate text-xs font-medium">
                      {grupo.professor?.nome ?? '—'}
                    </p>
                  </div>
                  <div className="space-y-1">
                    <p className="text-xs text-muted-foreground">Turma</p>
                    <p className="text-xs font-medium">
                      {grupo.turma?.nome ?? '—'}
                    </p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <EmptyState
          icon={Users2}
          title="Nenhum Grupo PAP definido"
          variant="compact"
        />
      )}
    </div>
  );
}
