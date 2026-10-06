import { Head, router } from '@inertiajs/react';
import { index } from '@/actions/App/Http/Controllers/Tenant/PautaController';
import { PautasHeader } from './components/header';
import { TurmasTable } from './components/turmas-table';

export default function Index({
  instituicao,
  instituicoes = [],
  cursos = [],
  turmas = [],
  anosLectivos = [],
  filtros = {},
}) {
  const visitarFiltros = (instituicaoId, cursoId, anoLectivoId) => {
    router.visit(
      index({
        query: {
          instituicao_id: instituicaoId,
          curso_tutelado_id: cursoId || null,
          ano_lectivo_id: anoLectivoId,
          search: filtros.search || null,
        },
      }),
      {
        only: ['instituicao', 'cursos', 'turmas', 'filtros'],
        preserveState: true,
        preserveScroll: true,
      },
    );
  };

  const handleInstituicaoChange = (instituicaoId) => {
    visitarFiltros(instituicaoId, '', filtros.ano_lectivo_id);
  };

  const handleCursoChange = (cursoId) => {
    visitarFiltros(
      filtros.instituicao_id,
      cursoId === 'todos' ? '' : cursoId,
      filtros.ano_lectivo_id,
    );
  };

  const handleAnoLectivoChange = (anoLectivoId) => {
    visitarFiltros(
      filtros.instituicao_id,
      filtros.curso_tutelado_id,
      anoLectivoId,
    );
  };

  const handlePageChange = (page) => {
    router.visit(
      index({
        query: {
          ...filtros,
          page,
          per_page: turmas.per_page,
        },
      }),
      {
        only: ['turmas'],
        preserveScroll: true,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <Head title="Pautas" />

      <PautasHeader
        instituicao={instituicao}
        instituicoes={instituicoes}
        cursos={cursos}
        anosLectivos={anosLectivos}
        filtros={filtros}
        onInstituicaoChange={handleInstituicaoChange}
        onCursoChange={handleCursoChange}
        onAnoLectivoChange={handleAnoLectivoChange}
      />

      <TurmasTable
        turmas={turmas.data ?? []}
        filtros={filtros}
        pagination={turmas}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
