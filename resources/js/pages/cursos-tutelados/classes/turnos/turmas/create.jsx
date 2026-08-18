import { useForm } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { store } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function Create({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  anosLectivos = [],
  can = {},
}) {
  const { data, setData, post, processing, errors } = useForm({
    nome: '',
    max_alunos: '',
  });

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    post(store(params).url, { preserveScroll: true });
  };

  return (
    <TurmaForm
      title="Adicionar Turma"
      description="Preencha os dados abaixo para adicionar uma nova turma."
      submitLabel="Adicionar Turma"
      params={params}
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      onSubmit={handleSubmit}
    />
  );
}
