export interface Curso {
  id: number;
  nome: string;
}

export interface CursoEdit extends Curso {
  duracao_anos: number | string;
  descricao: string;
}

export type CursoCreate = Omit<CursoEdit, 'id'>;

export type CursoShow = CursoEdit;
