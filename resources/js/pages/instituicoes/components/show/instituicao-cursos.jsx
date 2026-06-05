'use client';

import { TabContentCursos } from '../tabs/tab-content-cursos';

export function InstituicaoCursos({ cursos, instituicaoId }) {
  const handleDelete = (id) => {
    if (confirm('Tem certeza que deseja excluir esse curso?')) {
      // deleteFn(id);
    }
  };

  return (
    <TabContentCursos
      data={cursos}
      instituicaoId={instituicaoId}
    />
  );
}
