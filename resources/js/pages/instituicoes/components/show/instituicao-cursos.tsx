"use client"

import { TabContentCursos } from "../tabs/tab-content-cursos"

interface Curso {
  id: number;
  nome: string;
  instituicao_tutora?: string | null;
  // duracao_anos?: number;
}

interface Props {
  cursos: Curso[];
  instituicaoId: number;
}

export function InstituicaoCursos({ cursos, instituicaoId }: Props) {
  const handleDelete = (id: number) => {
    if (confirm("Tem certeza que deseja excluir esse curso?")) {
      // deleteFn(id);
    }
  };
  return (
    <TabContentCursos
      data={cursos}
      instituicaoId={instituicaoId}
     // deleteFn={handleDelete}
    />);
}