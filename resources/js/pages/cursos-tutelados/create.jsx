import { useForm } from '@inertiajs/react';
import { store } from '@/routes/cursos-tutelados';
import { CursoForm } from './components/forms/create.form';

export default function Create({ instituicao, classes, cursos }) {
  const { post, data, setData, processing, errors } = useForm({
    curso_id: '',
    classes: [],
    nome: '',
    duracao_anos: '',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    post(store({ instituicao: instituicao.id }), {
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
      <CursoForm
        title="Novo curso tutelado"
        classes={classes}
        cursos={cursos}
        data={data}
        setData={setData}
        errors={errors}
        processing={processing}
        onSubmit={handleSubmit}
      />
    </div>
  );
}
