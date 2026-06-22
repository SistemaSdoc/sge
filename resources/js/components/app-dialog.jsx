import {
  AlertDialog,
  AlertDialogContent,
  AlertDialogHeader,
  AlertDialogTitle,
  AlertDialogDescription,
  AlertDialogFooter,
  AlertDialogCancel,
  AlertDialogAction,
} from '@/components/ui/alert-dialog';
import { useDialogStore } from '@/stores/dialog.store';

/**
 * Componente visual global do dialog.
 *
 * Lê o estado do store e renderiza o dialog correcto.
 *
 * Deve ser montado uma única vez no AppLayout.
 */

export function AppDialog() {
  const {
    open,
    type,
    title,
    description,
    confirmLabel,
    cancelLabel,
    loading,
    confirmFn,
    closeDialog,
    setLoading,
  } = useDialogStore();
  
  /**
   * Executada quando o usuário clica no botão de confirmação.
   *
   * Activa loading -> executa confirmFn -> fecha o dialog.
   */
  const handleConfirm = async () => {
    if (!confirmFn) return closeDialog();

    setLoading(true);
    await confirmFn();
    closeDialog();
  };

  return (
    <AlertDialog open={open} onOpenChange={closeDialog}>
      <AlertDialogContent>
        <AlertDialogHeader>
          <AlertDialogTitle>{title}</AlertDialogTitle>
          {description && (
            <AlertDialogDescription>{description}</AlertDialogDescription>
          )}
        </AlertDialogHeader>

        <AlertDialogFooter>
          {type !== 'alert' && (
            <AlertDialogCancel onClick={closeDialog} disabled={loading}>
              {cancelLabel}
            </AlertDialogCancel>
          )}

          <AlertDialogAction
            onClick={handleConfirm}
            disabled={loading}
            variant={type === 'delete' ? 'destructive' : 'default'}
          >
            {loading ? 'A processar...' : confirmLabel}
          </AlertDialogAction>
        </AlertDialogFooter>
      </AlertDialogContent>
    </AlertDialog>
  );
}
