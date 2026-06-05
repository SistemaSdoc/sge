import { InstituicaoTable } from './components/instituicao-table';

export default function Index({ instituicoes, deleteFn }) {
  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <InstituicaoTable instituicoes={instituicoes} deleteFn={deleteFn} />
    </div>
  );
}
