/** Tipos dos dados enviados pelo backend à página de Quadro de Honra. */
export type QuadroHonraResumo = {
  id: string;
  ano_lectivo_id: string;
  ano_lectivo: string | null;
  periodo: number;
  media_minima: number | string;
  gerado_em: string | null;
  alunos: AlunoQuadroHonra[];
};

export type AlunoQuadroHonra = {
  id: string;
  posicao: number;
  nome: string;
  avatar_url: string | null;
  curso: string;
  classe: string;
  turma: string;
  media: number;
};

export type LinkPaginacao = {
  url: string | null;
  label: string;
  active: boolean;
};

export type Paginacao<T> = {
  data: T[];
  links: LinkPaginacao[];
  current_page: number;
  last_page: number;
  total: number;
};

export type FiltroQuadrosHonra = {
  periodo: '' | '1' | '2' | '3';
};
