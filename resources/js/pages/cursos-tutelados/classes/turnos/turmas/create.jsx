import { useForm } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function Create({ instituicao, cursoTutelado, cursoClasse, cursoClasseTurno, can = {} }) {
  const { data, setData, post, processing, errors } = useForm({
    nome: '',
    max_alunos: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(
      store({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
        cursoClasse: cursoClasse.id,
        cursoClasseTurno: cursoClasseTurno.id,
      }).url,
      { preserveScroll: true },
    );
  };

  return (
    <TurmaForm
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      onSubmit={handleSubmit}
    />
  );
}