import { create } from 'zustand';

/**
 * Store global para gestão de drawers.
 * Usado para formulários e acções contextuais.
 */
export const useDrawerStore = create((set) => ({
  // Estado inicial
  open: false,
  title: '',
  description: '',
  content: null, // O formulário ou conteúdo a renderizar
  className: '',

  // Acções

  /**
   * Abre o drawer com as opções fornecidas.
   * Faz merge com o estado actual - só passas o que precisas.
   */
  openDrawer: (options) => set({ ...options, open: true }),

  /**
   * Fecha o drawer e limpa o conteúdo.
   */
  closeDrawer: () => set({ open: false, content: null, className: '' }),
}));