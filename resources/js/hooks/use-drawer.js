import { useDrawerStore } from '@/stores/drawer.store';

/**
 * Hook que simplifica a abertura de drawers.
 * Usa este hook nas páginas em vez de chamar o store directamente.
 */
export function useDrawer() {
  const { openDrawer, closeDrawer } = useDrawerStore();

  /**
   * Abre o drawer com um formulário ou conteúdo arbitrário.
   * O conteúdo é responsável pelo seu próprio submit e por chamar closeDrawer no sucesso.
   *
   * @param {object} options
   * @param {string} options.title        - Título do drawer
   * @param {string} options.description  - Descrição opcional
   * @param {ReactNode} options.content   - Formulário ou JSX a renderizar
   */
  const openForm = (options) => openDrawer({ ...options });

  return { openForm, closeDrawer };
}
