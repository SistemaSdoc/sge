import { router } from "@inertiajs/react";
import { InstituicaoTable } from "./components/instituicao-table"

interface Instituicao {
  id: number;
  nome: string;
  sigla: string;
  tipo: string;
  email: string;
  telefone: string;
  endereco: string;
  logo: string;

}

interface props {
  instituicoes: Instituicao[];
  deleteFn: (id: number) => void;
}



export default function Index({ instituicoes, deleteFn }: props) {
  const isEmpty = !instituicoes || instituicoes.length === 0;

  const excluir = (id: number) => {
    if (confirm("Tem certeza que deseja excluir essa instituição?")) {
      deleteFn(id);
    }
  };

  return (
    <div>
      <InstituicaoTable
        instituicoes={instituicoes}
        deleteFn={deleteFn}
      />
    </div>
  )
}