import { router } from '@inertiajs/react';
import { TabContentCursos } from '../tabs/tab-content-cursos';
import { show } from '@/actions/App/Http/Controllers/Tenant/InstituicaoController';

export function InstituicaoCursos({
  cursos,
  instituicaoId,
  deleteFn,
  can = {},
}) {
  const handlePageChange = (page) => {
    router.visit(show({ id: instituicaoId }).url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <TabContentCursos
      data={cursos.data}
      instituicaoId={instituicaoId}
      deleteFn={deleteFn}
      can={can}
      pagination={{
        current_page: cursos.current_page,
        last_page: cursos.last_page,
      }}
      onPageChange={handlePageChange}
    />
  );
}
