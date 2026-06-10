import { useForm } from '@inertiajs/react';
import { CursoForm } from './components/forms/edit.form';

export default function Edit({
  instituicao,
  cursoTutelado,
  classes,
  instituicoes,
}) {
  const { data, setData, put, processing, errors } = useForm({
    duracao_anos: cursoTutelado?.curso?.duracao_anos ?? '',
    instituicao_tutora_id: cursoTutelado?.instituicao_tutora?.id ?? '',
    classes: Array.isArray(cursoTutelado?.classes) ? cursoTutelado.classes : [],
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    put(
      `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`,
      {
        preserveScroll: true,
      },
    );
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-semibold">Editar Curso Tutelado</h1>

      <CursoForm
        title="Editar curso tutelado"
        classes={classes}
        instituicoes={instituicoes}
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
