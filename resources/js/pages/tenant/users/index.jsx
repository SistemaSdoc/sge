import { Head, router } from '@inertiajs/react';
import {
  destroy,
  index,
} from '@/actions/App/Http/Controllers/Tenant/UserController';
import { useDialog } from '@/hooks/use-dialog';
import { UserTable } from './components/user-table';

export default function Index({ users, roles, allPermissions, filters }) {
  const { deleteConfirm } = useDialog();

  const handleDelete = (user) => {
    const isAluno = user.roles.includes('Aluno');
    const inscricao = user.aluno_matricula || 'associada';

    deleteConfirm({
      title: 'Remover usuário?',
      description: isAluno
        ? `Esta acção removerá a conta de ${user.nome} e a inscrição ${inscricao} se não houver histórico académico ou financeiro. Com histórico, a remoção será bloqueada; anule a matrícula em vez de remover.`
        : `Esta acção removerá a conta de ${user.nome}.`,
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(destroy(user.id).url, {
          preserveScroll: true,
        }),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: {
        ...Object.fromEntries(new URLSearchParams(window.location.search)),
        page,
      },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Usuários" />

      <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
        <UserTable
          users={users}
          filters={filters}
          pagination={users}
          onPageChange={handlePageChange}
          deleteFn={handleDelete}
        />
      </div>
    </>
  );
}
