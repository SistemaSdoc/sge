import { Link, router } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Button } from '@/components/ui/button';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Minus, MoreHorizontalIcon } from 'lucide-react';
import {
  Tooltip,
  TooltipContent,
  TooltipTrigger,
} from '@/components/ui/tooltip';
import { Badge } from '@/components/ui/badge';
import { show  } from '@/actions/App/Http/Controllers/Tenant/Colegios/CursoClasseController';

export default function Show({ cursoTutelado, colegio  }) {
  const instituicaoId = cursoTutelado.instituicao.id;
  const cursoTuteladoId = cursoTutelado.id;
  const cursoId = cursoTutelado.curso.id;


  const handlePageChange = (param) => (page) => {
    router.visit(
      showCurso({ instituicao: instituicaoId, cursoTutelado: cursoTuteladoId })
        .url,
    );
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      {/* Header */}
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex w-full items-end justify-between p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-bold md:text-3xl">
                {cursoTutelado.curso.nome}
              </h1>

              <p className="text-sm font-bold opacity-90">
                {cursoTutelado.instituicao.nome}
              </p>

            </div>

          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
          <div>
            <p className="text-sm text-muted-foreground">Duração</p>

            <p className="text-sm font-bold">
              {cursoTutelado.curso.duracao_anos ? (
                `${cursoTutelado.curso.duracao_anos} anos`
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Classes</p>
            <div className="mt-1 flex flex-wrap gap-1">
              {cursoTutelado.classes?.length > 0 ? (
                <>
                  {cursoTutelado.classes.map((c) => (
                    <Tooltip key={c.id}>
                      <TooltipTrigger asChild>
                        <Link
                          href={
                            show({
                              instituicao: instituicaoId,
                              colegio: colegio.id,
                              cursoTutelado: cursoTuteladoId,
                              cursoClasse: c.id,
                            }).url
                          }
                          className="inline-block"
                        >
                          <Badge>{c.nome}</Badge>
                        </Link>
                      </TooltipTrigger>

                      <TooltipContent side="bottom">
                        {c.turnos?.length > 0 ? (
                          <ul className="flex gap-3 text-xs">
                            {c.turnos.map((turno, i) => (
                              <li key={i}>{turno}</li>
                            ))}
                          </ul>
                        ) : (
                          <p className="text-xs">Sem turnos definidos</p>
                        )}
                      </TooltipContent>
                    </Tooltip>
                  ))}
                </>
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Contadores */}
      <div className="grid grid-cols-2 gap-4 md:grid-cols-3">
        <Card>
          <CardContent className="p-4">
            <p className="text-sm text-muted-foreground">Turmas</p>
            <h2 className="text-xl font-semibold">
              {cursoTutelado.contadores?.turmas ?? 0}
            </h2>
          </CardContent>
        </Card>

        <Card>
          <CardContent className="p-4">
            <p className="text-sm text-muted-foreground">Alunos</p>
            <h2 className="text-xl font-semibold">
              {cursoTutelado.contadores?.alunos ?? 0}
            </h2>
          </CardContent>
        </Card>
      </div>

    </div>
  );
}
