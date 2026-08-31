import { router, Link } from '@inertiajs/react';
import { show } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { show as showColegio } from '@/actions/App/Http/Controllers/Tenant/Colegios/GrupoPapController';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { ArrowUpRightIcon, Users2, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';

const MAX_ELEMENTOS_VISIVEIS = 2;

function ElementosBadges({ elementos = [] }) {
  const visiveis = elementos.slice(0, MAX_ELEMENTOS_VISIVEIS);
  const restantes = elementos.length - MAX_ELEMENTOS_VISIVEIS;

  return (
    <div className="flex flex-wrap gap-1">
      {visiveis.map((elemento) => (
        <Badge
          key={elemento.id}
          variant="secondary"
          asChild
          onClick={(e) => e.stopPropagation()}
          className="hover:underline"
        >
          <Link href={`/dashboard/alunos/${elemento.id}`}>
            {elemento.nome?.split(' ').slice(0, 2).join(' ')}
            <ArrowUpRightIcon size={10} className="ml-1" />
          </Link>
        </Badge>
      ))}

      {restantes > 0 && (
        <Badge
          variant="outline"
          className="cursor-default text-muted-foreground"
          onClick={(e) => e.stopPropagation()}
        >
          +{restantes}
        </Badge>
      )}

      {elementos.length === 0 && (
        <span className="text-xs text-muted-foreground">Sem elementos</span>
      )}
    </div>
  );
}

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
                  <p className="truncate text-xs font-medium">
                    {grupo.professor?.nome ?? (
                      <span className="text-destructive">Sem tutor</span>
                    )}
                  </p>
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
