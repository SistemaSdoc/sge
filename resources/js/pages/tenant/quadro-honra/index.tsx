import { Head } from '@inertiajs/react';
import { Award } from 'lucide-react';
import { FiltrosQuadrosHonra } from '@/components/tenant/quadro-honra/filtros-quadros-honra';
import { FormGerarQuadroHonra } from '@/components/tenant/quadro-honra/form-gerar-quadro-honra';
import { ListaQuadrosHonra } from '@/components/tenant/quadro-honra/lista-quadros-honra';
import type {
  AnoLectivoOption,
  CursoOption,
  FiltroQuadrosHonra,
  Paginacao,
  QuadroHonraResumo,
} from '@/types/quadro-honra';

type QuadroHonraIndexProps = {
  quadros: Paginacao<QuadroHonraResumo>;
  anosLectivos: AnoLectivoOption[];
  anoAtivoId: string | null;
  cursos: CursoOption[];
  filtros: FiltroQuadrosHonra;
  can: {
    gerarGeral: boolean;
    gerarCurso: boolean;
  };
};

/** Página Inertia que combina geração, filtros e histórico autorizado. */
export default function QuadroHonraIndex({
  quadros,
  anosLectivos,
  anoAtivoId,
  cursos,
  filtros,
  can,
}: QuadroHonraIndexProps) {
  return (
    <>
      <Head title="Quadro de Honra" />

      <main className="mx-auto flex w-full max-w-7xl flex-col gap-6 p-4 sm:p-6">
        <header className="flex items-start gap-3">
          <div className="rounded-md bg-primary/10 p-2 text-primary">
            <Award className="size-5" aria-hidden="true" />
          </div>
          <div>
            <h1 className="text-2xl font-semibold tracking-tight">
              Quadro de Honra
            </h1>
            <p className="mt-1 text-sm text-muted-foreground">
              Consulte os quadros gerados ou gere o quadro geral e os quadros
              por curso.
            </p>
          </div>
        </header>

        {(can.gerarGeral || can.gerarCurso) && (
          <FormGerarQuadroHonra
            anosLectivos={anosLectivos}
            anoAtivoId={anoAtivoId}
            cursos={cursos}
            podeGerarGeral={can.gerarGeral}
            podeGerarCurso={can.gerarCurso}
          />
        )}

        <FiltrosQuadrosHonra anosLectivos={anosLectivos} filtros={filtros} />
        <ListaQuadrosHonra quadros={quadros} filtros={filtros} />
      </main>
    </>
  );
}
