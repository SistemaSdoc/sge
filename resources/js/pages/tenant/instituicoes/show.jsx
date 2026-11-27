import { router } from '@inertiajs/react';
import { InstituicaoCabecalho } from './components/show/instituicao-cabecalho';
import { InstituicaoCursos } from './components/show/instituicao-cursos';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { useDialog } from '@/hooks/use-dialog';

export default function Show({ can, instituicao, cursos }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (cursoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O curso será eliminado permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () =>
        router.delete(
          destroy({ instituicao: instituicao.id, cursoTutelado: cursoId }),
        ).url,
    });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <InstituicaoCabecalho data={instituicao} can={can} />

      <InstituicaoCursos
        cursos={cursos}
        instituicaoId={instituicao.id}
        deleteFn={handleDelete}
        can={can}
      />
    </div>
  );
}
