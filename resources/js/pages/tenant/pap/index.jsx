import { GrupoPapCards } from './components/grupo-pap-cards';
import { Head, router } from '@inertiajs/react';
import { useState, useMemo } from 'react';
import { Header } from './components/header';

export default function Index({
  instituicao,
  instituicoes = [],
  cursosTutelados,
  gruposPap = [],
  anosLectivos = [],
  anoLectivoId,
}) {
  const [filtroInstituicao, setFiltroInstituicao] = useState(
    instituicao?.id ?? 'todas',
  );
  const [filtroCurso, setFiltroCurso] = useState('todos');

  const grupos = gruposPap.data ?? [];

  const cursos = useMemo(() => {
    const map = new Map();
    cursosTutelados?.forEach((curso) => {
      if (
        filtroInstituicao === 'todas' ||
        curso.instituicao_id === filtroInstituicao
      ) {
        map.set(curso.id, curso);
      }
    });
    return Array.from(map.values());
  }, [cursosTutelados, filtroInstituicao]);

  const gruposFiltrados = useMemo(() => {
    return grupos.filter((g) => {
      const passaInstituicao =
        filtroInstituicao === 'todas' ||
        g.instituicao?.id === filtroInstituicao;
      const passaCurso =
        filtroCurso === 'todos' || g.cursoTutelado?.id === filtroCurso;
      return passaInstituicao && passaCurso;
    });
  }, [grupos, filtroInstituicao, filtroCurso]);

  const handleAnoLectivoChange = (value) => {
    router.visit(window.location.pathname, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <>
      <Head title="Grupos Pap" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
        <Header
          instituicao={instituicao}
          instituicoes={instituicoes}
          cursosTutelados={cursos}
          filtroInstituicao={filtroInstituicao}
          onInstituicaoChange={(value) => {
            setFiltroInstituicao(value);
            setFiltroCurso('todos');
          }}
          filtroCurso={filtroCurso}
          onCursoChange={setFiltroCurso}
          anosLectivos={anosLectivos}
          anoLectivoId={anoLectivoId}
          onAnoLectivoChange={handleAnoLectivoChange}
        />

        <GrupoPapCards grupos={gruposFiltrados} />
      </div>
    </>
  );
}
