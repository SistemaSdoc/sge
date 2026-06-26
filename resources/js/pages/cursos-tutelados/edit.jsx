import { useForm } from '@inertiajs/react';
import { CursoForm } from './components/forms/edit.form';
import { update } from '@/actions/App/Http/Controllers/CursoTuteladoController';

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
      update({
        instituicao: instituicao.id,
        cursoTutelado: cursoTutelado.id,
      }).url,
      { preserveScroll: true },
    );
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
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
