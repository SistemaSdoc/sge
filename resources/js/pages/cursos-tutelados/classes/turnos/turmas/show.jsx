import { Loader2, Minus } from 'lucide-react';
import { Link, router, usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { MoreHorizontalIcon } from 'lucide-react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { TabAlunos } from './components/tabs/tab-alunos';
import { TabRecurso } from './components/tabs/tab-recurso';
import { TabGruposPAP } from './components/tabs/tab-grupos-pap';
import { TabDisciplinas } from './components/tabs/tab-disciplinas';
import { Badge } from '@/components/ui/badge';
import { preview } from '@/actions/App/Http/Controllers/ProgressaoController';

export default function Show({
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  alunos, // ← NOVO: recebe paginação do backend
  disciplinas, // ← NOVO: recebe paginação do backend
}) {
  const { url } = usePage();

  // Extract IDs from URL: /instituicoes/{id}/cursos-tutelados/{id}/classes/{id}/turnos/{id}/turmas/{id}
  const urlParts = url.split('/').filter(Boolean);
  const instituicaoIdx = urlParts.indexOf('instituicoes');
  const instituicaoId =
    urlParts[instituicaoIdx + 1] || cursoTutelado?.instituicaoTutoraId;

  const cursoTuteladoId = cursoTutelado.id;
  const cursoClasseId = cursoClasse.id;
  const cursoClasseTurnoId = cursoClasseTurno.id;
  const turmaId = turma.id;

  const classe = turma.curso_classe_turno?.curso_classe?.classe;
  const turno = turma.curso_classe_turno?.turno;

  // ── Buscar pauta final para saber quantos alunos estão em recurso ──
  const { pautaRecurso } = usePage().props;

  const totalRecurso = pautaRecurso?.resumo?.total ?? 0;

  // base para as rotas nested
  const baseUrl = `/dashboard/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      {/* Header */}
      <Card className="overflow-hidden pt-0! pb-0">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex w-full items-end justify-between p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-semibold md:text-3xl">
                {turma.nome}
              </h1>
              <p className="text-sm opacity-90">
                {classe?.nome} — {turno?.nome}
              </p>
            </div>

            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  variant="ghost"
                  size="icon"
                  className="text-white hover:bg-white/20"
                >
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end">
                <DropdownMenuItem onClick={() => router.visit(`#`)}>
                  Editar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  onClick={() =>
                    router.visit(
                      preview({
                        instituicao: instituicaoId,
                        cursoTutelado: cursoTuteladoId,
                        cursoClasse: cursoClasseId,
                        cursoClasseTurno: cursoClasseTurnoId,
                        turma: turmaId,
                      }).url,
                    )
                  }
                >
                  Progressão de Alunos
                </DropdownMenuItem>

                {/* ── NOVO ── */}
                {totalRecurso > 0 && (
                  <DropdownMenuItem
                    className="text-blue-600"
                    onClick={() =>
                      router.visit(
                        `/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/recurso`,
                      )
                    }
                  >
                    Lançar Recurso
                    <Badge className="ml-auto bg-blue-50 text-xs text-blue-600">
                      {totalRecurso} alunos
                    </Badge>
                  </DropdownMenuItem>
                )}
                <DropdownMenuSeparator />
                <DropdownMenuItem variant="destructive">
                  Remover
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 sm:grid-cols-2 md:grid-cols-4">
          <div>
            <p className="text-sm text-muted-foreground">Máximo de alunos</p>
            <p className="font-medium">
              {turma.max_alunos ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Total de alunos</p>
            <p className="font-medium">
              {alunos.total ?? turma.alunos?.length ?? 0}
            </p>{' '}
            {/* ← CORRIGIDO */}
          </div>
          <div>
            <p className="text-sm text-muted-foreground">
              Total de disciplinas
            </p>
            <p className="font-medium">
              {disciplinas.total ??
                turma.curso_classe_turno?.classe_turno_disciplinas?.length ??
                0}{' '}
              {/* ← CORRIGIDO */}
            </p>
          </div>
        </CardContent>
      </Card>

      <Tabs defaultValue="alunos" className="w-full">
        <TabsList>
          <TabsTrigger value="alunos">Alunos</TabsTrigger>
          <TabsTrigger value="disciplinas">Disciplinas</TabsTrigger>
          {classe?.nome === '13ª' && (
            <TabsTrigger value="grupos-pap">Grupos para PAP</TabsTrigger>
          )}

          {/* ── NOVO: tab de recurso só aparece se houver alunos ── */}
          {totalRecurso > 0 && (
            <TabsTrigger value="recurso" className="text-blue-600">
              Recurso
              <Badge className="ml-2 bg-blue-50 text-xs text-blue-600">
                {totalRecurso}
              </Badge>
            </TabsTrigger>
          )}
        </TabsList>

        <TabsContent value="alunos">
          <TabAlunos
            turma={{ ...turma, alunos: alunos.data ?? [] }}
            pagination={alunos}
            instituicaoId={instituicaoId}
            cursoTuteladoId={cursoTuteladoId}
            cursoClasseId={cursoClasseId}
            cursoClasseTurnoId={cursoClasseTurnoId}
          />
          {/* REMOVIDO: onPageChange={handlePageChange('page_alunos')} */}
        </TabsContent>

        <TabsContent value="disciplinas">
          <TabDisciplinas
            turma={{
              ...turma,
              curso_classe_turno: {
                ...turma.curso_classe_turno,
                classe_turno_disciplinas: disciplinas.data ?? [],
              },
            }}
            pagination={disciplinas}
            instituicaoId={instituicaoId}
            cursoTuteladoId={cursoTuteladoId}
            cursoClasseId={cursoClasseId}
            cursoClasseTurnoId={cursoClasseTurnoId}
          />
          {/* REMOVIDO: onPageChange={handlePageChange('page_disciplinas')} */}
        </TabsContent>

        {classe?.nome === '13ª' && (
          <TabsContent value="grupos-pap">
            <TabGruposPAP
              turma={turma}
              instituicaoId={instituicaoId}
              cursoTuteladoId={cursoTuteladoId}
              cursoClasseId={cursoClasseId}
              cursoClasseTurnoId={cursoClasseTurnoId}
            />
          </TabsContent>
        )}

        {totalRecurso > 0 && (
          <TabsContent value="recurso">
            <TabRecurso
              alunos={pautaRecurso?.alunos ?? []}
              instituicaoId={instituicaoId}
              cursoId={cursoTuteladoId}
              turmaId={turmaId}
            />
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
