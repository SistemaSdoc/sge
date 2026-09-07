import { useState } from 'react';
import { useDrawer } from '@/hooks/use-drawer';
import { Head, router } from '@inertiajs/react';
import { GrupoPapCards } from './components/grupo-pap-cards';
import { Header } from './components/header';
import GrupoPapForm from './components/grupo-pap-form';

export default function Index({
  instituicao,
  instituicoes,
  cursosTutelados,
  gruposPap,
  anoLectivoId,
  anosLectivos,
  can,
}) {
  const { openForm, closeDrawer } = useDrawer();

  const [filtroInstituicao, setFiltroInstituicao] = useState(
    String(instituicao?.id ?? ''),
  );
  const [filtroAnoLectivo, setFiltroAnoLectivo] = useState(
    String(anoLectivoId ?? ''),
  );
  const [filtroCurso, setFiltroCurso] = useState('');

  const handleAdicionarGrupo = () => {
    openForm({
      title: 'Criar Novo Grupo PAP',
      description: 'Preenche os dados para criar um novo grupo',
      content: (
        <GrupoPapForm
          instituicao={instituicao}
          cursosTutelados={cursosTutelados}
          closeDrawer={closeDrawer}
          onSuccess={() => {
            closeDrawer();
            router.reload({ only: ['gruposPap'] });
          }}
        />
      ),
    });
  };

  const handleCursoChange = (cursoId) => {
    setFiltroCurso(String(cursoId ?? ''));
    router.visit(window.location.pathname, {
      data: {
        curso_tutelado_id: cursoId || null,
        ano_lectivo_id: filtroAnoLectivo,
        instituicao_id: filtroInstituicao,
      },
      only: ['gruposPap'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleInstituicaoChange = (instituicaoId) => {
    setFiltroInstituicao(String(instituicaoId ?? ''));
    setFiltroCurso('');
    router.visit(window.location.pathname, {
      data: {
        instituicao_id: instituicaoId,
        ano_lectivo_id: filtroAnoLectivo,
        curso_tutelado_id: null,
      },
      only: ['gruposPap', 'cursosTutelados', 'instituicao', 'anoLectivoId'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    setFiltroAnoLectivo(String(value ?? ''));
    setFiltroCurso('');
    router.visit(window.location.pathname, {
      data: {
        ano_lectivo_id: value,
        curso_tutelado_id: null,
        instituicao_id: filtroInstituicao,
      },
      only: ['gruposPap', 'cursosTutelados', 'anoLectivoId', 'instituicao'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Grupos PAP" />

      <Header
        instituicao={instituicao}
        instituicoes={instituicoes}
        cursosTutelados={cursosTutelados}
        filtroInstituicao={filtroInstituicao}
        onInstituicaoChange={handleInstituicaoChange}
        filtroCurso={filtroCurso}
        onCursoChange={handleCursoChange}
        anosLectivos={anosLectivos}
        anoLectivoId={filtroAnoLectivo}
        onAnoLectivoChange={handleAnoLectivoChange}
        onAddGrupo={handleAdicionarGrupo}
      />

      <div className="mt-6">
        <GrupoPapCards can={can} grupos={gruposPap.data ?? []} />
      </div>
    </div>
  );
}
