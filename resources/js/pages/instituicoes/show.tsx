
import { InstituicaoCabecalho } from "./components/show/instituicao-cabecalho"
import { InstituicaoCursos } from "./components/show/instituicao-cursos"


interface Props {
  instituicao: {
    id: number;
    nome: string;
    sigla: string;
    email: string;
    telefone: string;
    endereco: string;
    logo: string | null;
  };
  cursos: {
    id: number;
    nome: string;
    instituicao_tutora?: string | null;
  }[];
  storageUrl: string;
}

export default function Show({ instituicao, cursos, storageUrl }: Props) {

  return (
    <div className="w-full max-w-6xl mx-auto space-y-6">
      <InstituicaoCabecalho data={instituicao} storageUrl={storageUrl} />
      <InstituicaoCursos cursos={cursos} instituicaoId={instituicao.id} />
    </div>
  )
}