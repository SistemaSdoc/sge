'use client';

import { router } from '@inertiajs/react';
import { TabContentCursos } from '../tabs/tab-content-cursos';

export function InstituicaoCursos({ cursos, instituicaoId, deleteFn }) {
  const handlePageChange = (page) => {
    router.visit(`/instituicoes/${instituicaoId}`, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <TabContentCursos
      data={cursos.data}
      instituicaoId={instituicaoId}
      deleteFn={deleteFn}
      pagination={{
        current_page: cursos.current_page,
        last_page: cursos.last_page,
      }}
      onPageChange={handlePageChange}
    />
  );
}