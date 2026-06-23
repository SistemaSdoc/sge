import { useDialogStore } from '@/stores/dialog.store';

/**
 * Hook que simplifica a abertura de dialogs.
 *
 * Esconde detalhes repetitivos do store - type, labels default, etc
 *
 * Usa este hook nas páginas em vez de chamar o store directamente.
 */

export function useDialog() {
  const { openDialog, closeDialog } = useDialogStore();

  /**
   * Dialog de confirmação genérico.
   *
   * Botão de confirmação azul (default)
   */
  const confirm = (options) =>
    openDialog({
      type: 'confirm',
      confirmLabel: 'Confirmar',
      cancelLabel: 'Cancelar',
      ...options,
    });

  /**
   * Dialog de apagar - acção destrutiva.
   *
   * Botão de confirmação vermelho (destructive).
   */

  const deleteConfirm = (options) =>
    openDialog({
      type: 'delete',
      confirmLabel: 'Apagar',
      cancelLabel: 'Cancelar',
      ...options,
    });

  /**
   * Dialog de aviso simples — sem cancelar.
   *
   *Só um botão Ok para fechar.
   */
  const alert = (options) =>
    openDialog({
      type: 'alert',
      confirmLabel: 'OK',
      ...options,
    });

  /**
   * Dialog com formulário — sem botões fixos.
   * O formulário é responsável pelo seu próprio submit e por chamar closeDialog no sucesso.
   */
  const openForm = (options) =>
    openDialog({
      type: 'form',
      ...options,
    });

  return { confirm, deleteConfirm, alert, openForm, closeDialog };
}
