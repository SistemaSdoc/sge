import { router } from '@inertiajs/react';
import { CursoForm } from './components';

export default function Edit({
  instituicao,
  cursoTutelado,
  classes,
  instituicoes,
}) {
  return (
    <div className="mx-auto w-full max-w-5xl px-4 py-6">
      <h1 className="mb-4 text-2xl font-semibold">Editar Curso Tutelado</h1>

      <CursoForm
        title="Editar curso tutelado"
        classes={classes}
        instituicoes={instituicoes}
        isLoading={false}
        defaultValues={{
          duracao_anos: cursoTutelado.curso.duracao_anos ?? '',
          instituicao_tutora_id: String(cursoTutelado.instituicao_tutora.id),
          classes: cursoTutelado.classes ?? [],
        }}
        submitFn={(formData) => {
          router.put(
            `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`,
            formData,
            {
              preserveScroll: true,
              onSuccess: () => {
                router.visit(
                  `/instituicoes/${instituicao.id}/cursos-tutelados/${cursoTutelado.id}`,
                );
              },
            },
          );
        }}
      />
    </div>
  );
}
