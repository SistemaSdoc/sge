import { router, Link } from '@inertiajs/react';
import { UsersIcon } from 'lucide-react';
import { show as showColegio } from '@/actions/App/Http/Controllers/Tenant/Colegios/Pap/GrupoPapController';
import { show } from '@/actions/App/Http/Controllers/Tenant/Pap/GrupoPapController';
import { show as showProfessor } from '@/actions/App/Http/Controllers/Tenant/ProfessorController';
import { show as showUser } from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { ElementosBadges } from './elementos-badges';

export function GrupoPapCards({ grupos = [] }) {
  if (grupos.length === 0) {
    return (
      <EmptyState
        icon={UsersIcon}
        title="Nenhum grupo cadastrado"
        description="Tente ajustar os filtros acima."
        variant="compact"
      />
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
      {grupos.map((grupo) => (
        <Card
          key={grupo.id}
          className="flex flex-col gap-5 hover:cursor-pointer"
          onClick={() =>
            router.visit(
              (grupo.cross_tenant ? showColegio : show)({
                ...(grupo.cross_tenant
                  ? { colegio: grupo.instituicao.id }
                  : { instituicao: grupo.instituicao.id }),
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
            <div className="flex items-start justify-between gap-2">
              <CardTitle className="text-base leading-snug">
                {grupo.nome_grupo}
              </CardTitle>
              {grupo.cursoTutelado?.nome && (
                <Badge variant="outline" className="shrink-0 text-xs">
                  {grupo.cursoTutelado.nome}
                </Badge>
              )}
            </div>
            <CardDescription className="line-clamp-2">
              {grupo.tema_grupo}
            </CardDescription>
          </CardHeader>

          <CardContent className="flex-1 space-y-3">
            {/* Elementos */}
            <div className="space-y-1.5">
              <span className="text-xs text-muted-foreground">Elementos</span>
              <ElementosBadges elementos={grupo.elementos ?? []} />
            </div>

            {/* Tutor + Turma */}
            <div className="border-t pt-3">
              <div className="grid grid-cols-2 gap-3 text-sm">
                <div className="space-y-0.5">
                  <p className="text-xs text-muted-foreground">Tutor</p>
                  {grupo.professor?.can_view ? (
                    <Link
                      href={
                        grupo.professor.is_current_user
                          ? showUser.url({ user: grupo.professor.user_id })
                          : showProfessor.url({
                              professor: grupo.professor.id,
                            })
                      }
                      className="truncate text-xs font-medium hover:underline"
                      onClick={(event) => event.stopPropagation()}
                    >
                      {grupo.professor.nome}
                    </Link>
                  ) : (
                    <p className="truncate text-xs font-medium">
                      {grupo.professor?.nome ?? (
                        <span className="text-destructive">Sem tutor</span>
                      )}
                    </p>
                  )}
                </div>
                <div className="space-y-0.5">
                  <p className="text-xs text-muted-foreground">Turma</p>
                  <p className="text-xs font-medium">
                    {grupo.turma?.nome ?? '—'}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}
