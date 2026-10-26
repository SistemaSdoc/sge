import { useDrawer } from '@/hooks/use-drawer';
import { Head, router } from '@inertiajs/react';
import { index as grupoPapIndex } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import { useTableSearch } from '@/hooks/use-table-search';
import { GrupoPapCards } from './components/grupo-pap-cards';
import { Header } from './components/header';
import GrupoPapForm from './components/grupo-pap-form';

export default function Index({
  instituicao,
  instituicoes,
  cursosTutelados,
  cursosFiltro,
  gruposPap,
  anoLectivoId,
  anosLectivos,
  can,
  filters,
}) {
  const { openForm, closeDrawer } = useDrawer();
  const { search, onChange, submit, applied } = useTableSearch(
    filters?.search,
    {
      only: ['gruposPap', 'filters'],
    },
  );

  const visitarFiltros = (alteracoes, only = ['gruposPap', 'filters']) => {
    const query = { ...filters, ...alteracoes };
    delete query.page;

    router.visit(grupoPapIndex['/dashboard/pap']({ query }), {
      only,
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleAdicionarGrupo = () => {
    openForm({
      title: 'Criar novo grupo PAP',
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

  const handleInstituicaoChange = (instituicaoId) => {
    visitarFiltros(
      {
        instituicao_id: instituicaoId === 'todas' ? null : instituicaoId,
        curso_id: null,
      },
      [
        'gruposPap',
        'cursosTutelados',
        'instituicao',
        'instituicoes',
        'filters',
      ],
    );
  };

  const handleCursoChange = (cursoId) => {
    visitarFiltros({ curso_id: cursoId === 'todos' ? null : cursoId });
  };

  const handleAnoLectivoChange = (anoLectivoId) => {
    visitarFiltros({ ano_lectivo_id: anoLectivoId });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
      <Head title="Grupos PAP" />

      <Header
        can={can}
        instituicao={instituicao}
        instituicoes={instituicoes}
        cursosTutelados={cursosFiltro}
        filtroInstituicao={filters?.instituicao_id}
        onInstituicaoChange={handleInstituicaoChange}
        filtroCurso={filters?.curso_id}
        onCursoChange={handleCursoChange}
        anosLectivos={anosLectivos}
        anoLectivoId={filters?.ano_lectivo_id ?? anoLectivoId}
        onAnoLectivoChange={handleAnoLectivoChange}
        onAddGrupo={handleAdicionarGrupo}
        search={search}
        onSearchChange={onChange}
        onSearchSubmit={submit}
      />

      <div className="mt-6">
        <GrupoPapCards
          can={can}
          grupos={gruposPap.data ?? gruposPap ?? []}
          emptyTitle={
            applied
              ? 'Nenhum grupo PAP encontrado'
              : 'Nenhum Grupo PAP definido'
          }
          emptyDescription={
            applied
              ? 'Tenta ajustar a pesquisa.'
              : 'Ainda não existem grupos PAP para esta turma.'
          }
        />
      </div>
    </div>
  );
}
