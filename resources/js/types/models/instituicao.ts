export interface Instituicao {
  id: number;
  nome: string;
  sigla: string;
  tipo: string;
}

export interface InstituicaoEdit extends Instituicao {
  email: string;
  telefone: string;
  endereco: string;
  logo: string;
}

export type InstituicaoShow = InstituicaoEdit;

export type InstituicaoCreate = Omit<InstituicaoEdit, 'id'>;

