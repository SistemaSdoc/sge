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
import CursoTable from './components/aviso-table';

export default function Index({ avisos }) {
  const [avisoParaExcluir, setAvisoParaExcluir] = useState(null);

  const handleDelete = () => {
    if (avisoParaExcluir === null) {
      return;
    }

    router.delete(`/avisos/${avisoParaExcluir}`, {
      onFinish: () => setAvisoParaExcluir(null),
    });
  };

  return (
    <>
      <div className="mx-auto w-full max-w-7xl p-6">
        <CursoTable avisos={avisos} deleteFn={setAvisoParaExcluir} />
      </div>

      <Dialog
        open={avisoParaExcluir !== null}
        onOpenChange={() => setAvisoParaExcluir(null)}
      >
        <DialogContent>
          <DialogHeader>
            <DialogTitle>Tens a certeza?</DialogTitle>
            <DialogDescription>
              Esta acção é irreversível. O aviso será eliminado permanentemente.
            </DialogDescription>
          </DialogHeader>

          <DialogFooter>
            <Button variant="outline" onClick={() => setAvisoParaExcluir(null)}>
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
