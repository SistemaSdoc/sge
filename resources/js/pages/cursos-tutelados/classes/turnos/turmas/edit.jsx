import { useForm } from '@inertiajs/react';
import { TurmaForm } from './components/turma-form';
import { update } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function Edit({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  origem,
  anoLectivoId,
  anosLectivos = [],
  can = {},
}) {
  const { data, setData, put, processing, errors } = useForm({
    nome: turma?.nome ?? '',
    max_alunos: turma?.max_alunos ?? '',
    origem: origem,
    ano_lectivo_id: anoLectivoId,
  });

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma: turma.id,
  };

  const handleSubmit = (e) => {
    e.preventDefault();

    put(update(params).url, { preserveScroll: true });
  };

  return (
    <TurmaForm
      title="Editar Turma"
      description="Altere os dados da turma e salve as alterações."
      submitLabel="Salvar Alterações"
      params={params}
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      can={can}
      anosLectivos={anosLectivos}
      onSubmit={handleSubmit}
    />
  );
}
