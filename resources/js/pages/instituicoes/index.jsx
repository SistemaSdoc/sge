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
import { InstituicaoTable } from './components/instituicao-table';

export default function Index({ instituicoes }) {
  const [instituicaoParaExcluir, setInstituicaoParaExcluir] = useState(null);

  const handleDelete = () => {
    if (instituicaoParaExcluir === null) return;

    router.delete(`/instituicoes/${instituicaoParaExcluir}`, {
      onFinish: () => setInstituicaoParaExcluir(null),
    });
  };

  const handlePageChange = (page) => {
    router.visit('/instituicoes', {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <div className="mx-auto w-full max-w-7xl p-6">
        <InstituicaoTable
          instituicoes={instituicoes.data}
          deleteFn={setInstituicaoParaExcluir}
          pagination={{
            current_page: instituicoes.current_page,
            last_page: instituicoes.last_page,
          }}
          onPageChange={handlePageChange}
        />
      </div>

      <Dialog
        open={instituicaoParaExcluir !== null}
        onOpenChange={() => setInstituicaoParaExcluir(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Tens a certeza?</DialogTitle>
            <DialogDescription>
              Esta acção é irreversível. A instituição será eliminada permanentemente.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button variant="outline" onClick={() => setInstituicaoParaExcluir(null)}>
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