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
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
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
    content,
    size = 'md',
    closeDialog,
    setLoading,
  } = useDialogStore();

  const sizeClasses = {
    sm: 'max-w-sm!',
    md: 'max-w-md!',
    lg: 'max-w-lg!',
    xl: 'max-w-2xl!',
    full: 'max-w-4xl!',
  };

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

  if (type === 'form') {
    return (
      <Dialog open={open} onOpenChange={closeDialog}>
        <DialogContent
          className={sizeClasses[size]}
          onPointerDownOutside={(e) => e.preventDefault()}
        >
          <DialogHeader>
            <DialogTitle>{title}</DialogTitle>
            {description && (
              <DialogDescription>{description}</DialogDescription>
            )}
          </DialogHeader>
          {content}
        </DialogContent>
      </Dialog>
    );
  }

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
            <AlertDialogCancel
              type="button"
              onClick={closeDialog}
              disabled={loading}
            >
              {cancelLabel}
            </AlertDialogCancel>
          )}

          <AlertDialogAction
            type="button"
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
