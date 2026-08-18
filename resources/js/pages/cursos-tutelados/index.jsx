import { Head, router } from '@inertiajs/react';
import { CursosTuteladosTable } from './components/curso-tutelado-table';
import { index } from '@/actions/App/Http/Controllers/CursoTuteladoController';

export default function Index({ cursos, instituicao, can = {} }) {
  const handlePageChange = (page) => {
    router.visit(index({ id: instituicao?.id }).url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Instituições" />

      <CursosTuteladosTable
        data={cursos?.data || []}
        instituicaoId={instituicao?.id}
        can={can}
        pagination={{
          current_page: cursos.current_page,
          last_page: cursos.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
