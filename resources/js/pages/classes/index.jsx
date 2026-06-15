import { router } from '@inertiajs/react';
import { ClasseTable } from './components/classe-table';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/ClasseController';

export default function Index({ classes }) {
  const excluir = (id) => {
    if (confirm('Tem certeza que deseja excluir essa classe?')) {
      router.delete(destroy(id).url);
    }
  };

  const handlePageChange = (page) => {
    router.visit(index().url, {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <ClasseTable
        classes={classes}
        deleteFn={excluir}
        pagination={{
          current_page: classes.current_page,
          last_page: classes.last_page,
        }}
        onPageChange={handlePageChange}
      />
    </div>
  );
}
