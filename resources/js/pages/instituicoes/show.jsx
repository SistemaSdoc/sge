import { InstituicaoCabecalho } from './components/show/instituicao-cabecalho';
import { InstituicaoCursos } from './components/show/instituicao-cursos';

export default function Show({ instituicao, cursos, storageUrl }) {
  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <InstituicaoCabecalho data={instituicao} storageUrl={storageUrl} />
      <InstituicaoCursos cursos={cursos} instituicaoId={instituicao.id} />
    </div>
  );
}
