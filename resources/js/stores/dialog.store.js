import { create } from 'zustand';

/**
 * Store global para gestão de caixas de diálogo
 * Suporta três tipos: confirm, delete, alert
 */

export const useDialogStore = create((set) => ({
  // Estado inicial
  open: false,
  type: 'confirm',
  title: '',
  description: '',
  confirmLabel: 'Confirmar',
  cancelLabel: 'Cancelar',
  loading: false,
  confirmFn: null,
  content: null,

  // Acções

  /**
   * Abre o dialog com opções fornecidas.
   * Faz merge com o estado actual - só passamos o que precisarmos
   */
  openDialog: (options) => set({ ...options, open: true }),

  /**
   * Fecha o dialog e repõe loading
   */
  closeDialog: () => set({ open: false, loading: false, content: null }),

  /**
   * Activa o estado de loading enquanto a confirmFn está a executar.
   */
  setLoading: (value) => set({ loading: value }),
}));
