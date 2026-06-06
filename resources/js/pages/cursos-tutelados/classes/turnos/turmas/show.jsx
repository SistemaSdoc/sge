"use client";
import { Loader2, Minus } from "lucide-react";
import { useRouter } from "next/navigation";
import { useTurma } from "../../../../hooks/classes/turnos/turmas/useTurma";
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Button } from "@/components/ui/button";
import { MoreHorizontalIcon } from "lucide-react";
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs";
import { useRemoverProfessor } from "../../../../hooks/classes/turnos/turmas/useRemoverProfessor";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";
import { TabAlunos } from "../../../../components/classes/turnos/turmas/tabs/tab-alunos";
import { TabGruposPAP } from "../../../../components/classes/turnos/turmas/tabs/tab-grupos-pap";
import { TabDisciplinas } from "../../../../components/classes/turnos/turmas/tabs/tab-disciplinas";
import { usePauta } from "../../../../hooks/classes/turnos/turmas/usePauta";
import { TabRecurso } from "../../../../components/classes/turnos/turmas/tabs/tab-recurso"; // ← novo
import { Badge } from "@/components/ui/badge";

export function TurmaShow({
  instituicaoId,
  cursoId,
  classeId,
  turnoId,
  turmaId,
}) {
  const router = useRouter();
  const { data, isLoading } = useTurma(
    instituicaoId,
    cursoId,
    classeId,
    turnoId,
    turmaId,
  );
  const { mutate: removerProfessor } = useRemoverProfessor(
    instituicaoId,
    cursoId,
    turmaId,
  );

  // ── Buscar pauta final para saber quantos alunos estão em recurso ──
  const { data: pautaFinal } = usePauta({ turmaId, periodo: "final" });
  const totalRecurso = pautaFinal?.resumo?.recurso ?? 0;

  if (isLoading)
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="animate-spin size-8" />
      </div>
    );

  return (
    <div className="w-full max-w-6xl mx-auto space-y-6">
      {/* Header */}
      <Card className="overflow-hidden pt-0! pb-0">
        <div className="relative flex items-end w-full h-56 bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex items-end justify-between w-full p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-semibold md:text-3xl">
                {data?.nome}
              </h1>
              <p className="text-sm opacity-90">
                {data?.classe?.nome} — {data?.turno?.nome}
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
                <DropdownMenuItem
                  onClick={() =>
                    router.push(
                      `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/turmas/${turmaId}/edit`,
                    )
                  }
                >
                  Editar
                </DropdownMenuItem>
                <DropdownMenuSeparator />
                <DropdownMenuItem
                  onClick={() =>
                    router.push(
                      `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/progressao`,
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
                      router.push(
                        `/dashboard/instituicoes/${instituicaoId}/cursos/${cursoId}/classes/${classeId}/turnos/${turnoId}/turmas/${turmaId}/recurso`,
                      )
                    }
                  >
                    Lançar Recurso
                    <Badge className="ml-auto bg-blue-50 text-blue-600 text-xs">
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
              {data?.max_alunos ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Total de alunos</p>
            <p className="font-medium">{data?.alunos?.length ?? 0}</p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">
              Total de disciplinas
            </p>
            <p className="font-medium">{data?.disciplinas?.length ?? 0}</p>
          </div>
          {totalRecurso > 0 && (
            <div>
              <p className="text-sm text-muted-foreground">Alunos em recurso</p>
              <p className="font-medium">{totalRecurso}</p>
            </div>
          )}
        </CardContent>
      </Card>

      <Tabs defaultValue="alunos" className="w-full">
        <TabsList>
          <TabsTrigger value="alunos">Alunos</TabsTrigger>
          <TabsTrigger value="disciplinas">Disciplinas</TabsTrigger>

          {data?.classe?.nome === "13ª" && (
            <TabsTrigger value="grupos-pap">Grupos para PAP</TabsTrigger>
          )}

          {/* ── NOVO: tab de recurso só aparece se houver alunos ── */}
          {totalRecurso > 0 && (
            <TabsTrigger value="recurso" className="text-blue-600">
              Recurso
              <Badge className="ml-2 bg-blue-50 text-blue-600 text-xs">
                {totalRecurso}
              </Badge>
            </TabsTrigger>
          )}
        </TabsList>

        <TabsContent value="alunos">
          <TabAlunos
            data={data}
            cursoId={cursoId}
            turmaId={turmaId}
            instituicaoId={instituicaoId}
            classeId={classeId}
            turnoId={turnoId}
          />
        </TabsContent>

        <TabsContent value="disciplinas">
          <TabDisciplinas
            data={data}
            instituicaoId={instituicaoId}
            cursoId={cursoId}
            classeId={classeId}
            turnoId={turnoId}
            turmaId={turmaId}
            removerProfessorFn={removerProfessor}
          />
        </TabsContent>

        {data?.classe?.nome === "13ª" && (
          <TabsContent value="grupos-pap">
            <TabGruposPAP
              data={data}
              cursoId={cursoId}
              classeId={classeId}
              turnoId={turnoId}
              turmaId={turmaId}
              instituicaoId={instituicaoId}
            />
          </TabsContent>
        )}

        {/* ── NOVO: tab de recurso ── */}
        {totalRecurso > 0 && (
          <TabsContent value="recurso">
            <TabRecurso turmaId={turmaId} />
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
