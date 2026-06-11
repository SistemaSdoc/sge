import { router } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogFooter,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import CursoTable from './components/curso-table';

export default function Index({ cursos }) {
  const [cursoParaExcluir, setCursoParaExcluir] = useState(null);

  const handleDelete = () => {
    if (cursoParaExcluir === null) {
      return;
    }

    router.delete(`/cursos/${cursoParaExcluir}`, {
      onFinish: () => setCursoParaExcluir(null),
    });
  };

  const handlePageChange = (page) => {
    router.visit('/cursos', {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <div className="mx-auto w-full max-w-7xl p-6">
        <CursoTable 
        cursos={cursos.data}
         deleteFn={setCursoParaExcluir}
         pagination={{
          current_page: cursos.current_page,
          last_page: cursos.last_page,
        }}
        onPageChange={handlePageChange}
        />
      </div>

      <Dialog
        open={cursoParaExcluir !== null}
        onOpenChange={() => setCursoParaExcluir(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Tens a certeza?</DialogTitle>
            <DialogDescription>
              Esta acção é irreversível. O curso será eliminado permanentemente.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button variant="outline" onClick={() => setCursoParaExcluir(null)}>
              Cancelar
            </Button>

            <Button variant="destructive" onClick={handleDelete}>
              Eliminar
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
