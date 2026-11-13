/** Tipos dos dados enviados pelo backend à página de Quadro de Honra. */
export type AnoLectivoOption = {
  id: string;
  nome: string;
};

export type CursoOption = {
  id: string;
  nome: string;
};

export type TipoQuadroHonra = 'geral' | 'curso';

export type CandidatoQuadroHonra = {
  turma_aluno_id: string;
  nome_aluno: string;
  classe: string;
  curso: string;
  turma: string;
  media: number;
};

export type PrevisualizacaoQuadroHonra = {
  candidatos: CandidatoQuadroHonra[];
};

export type QuadroHonraResumo = {
  id: string;
  ano_lectivo_id: string;
  ano_lectivo: string | null;
  tipo: TipoQuadroHonra;
  curso: string | null;
  media_minima: number | string;
  quantidade_alunos: number;
  gerado_em: string | null;
  gerado_por: string | null;
  pdf_url: string;
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
  ano_lectivo_id: string;
  tipo: '' | TipoQuadroHonra;
};
