import { router, usePage } from '@inertiajs/react';

/**
 * Hook para gerir paginação, pesquisa e filtros via Inertia.
 *
 * Suporta múltiplas listas paginadas na mesma página usando query params
 *
 * separados por chave (ex: page_alunos, page_disciplinas).
 *
 * @param {string} [key='page'] - Chave do query param de paginação.
 *   Para uma lista: 'page' (default).
 *
 *   Para múltiplas listas: 'alunos', 'disciplinas', etc.
 *
 *   Gera automaticamente: page_{key}, search_{key}.
 *
 * @returns {{
 *   handlePageChange: (page: number) => void,
 *   handleSearch: (search: string) => void,
 *   handleFilter: (filters: Record<string, any>) => void
 * }}
 *
 * @example
 * // Uma lista
 * const { handlePageChange } = usePagination();
 *
 * @example
 * // Múltiplas listas na mesma página
 * const alunos = usePagination('alunos');
 *
 * const disciplinas = usePagination('disciplinas');
 *
 * <TabAlunos onPageChange={alunos.handlePageChange} onSearch={alunos.handleSearch} />
 * <TabDisciplinas onPageChange={disciplinas.handlePageChange} />
 *
 * @example
 * // Com filtros
 * const { handleFilter } = usePagination('alunos');
 * handleFilter({ estado: 'activo', turno: 'manha' });
 * // → ?estado=activo&turno=manha&page_alunos=1
 */
export function usePagination(key = 'page') {
  const { url } = usePage();

  /**
   * Navega para a URL actual com novos query params,
   * preservando scroll e estado das outras tabs.
   *
   * @param params
   */
  const navigate = (params) => {
    router.visit(url, {
      data: params,
      preserveScroll: true,
      preserveState: true,
    });
  };

  /**
   * Muda para a página indicada.
   * @param page
   */
  const handlePageChange = (page) => {
    navigate({ [`page_${key}`]: page });
  };

  /**
   * Pesquisa pelo termo indicado e reset a página para 1.
   *
   * Query param gerado: search_{key}
   * @param search
   */
  const handleSearch = (search) => {
    navigate({ [`search_${key}`]: search, [key]: 1 });
  };

  /**
   * Aplica filtros arbitrários e reset a página para 1.
   * @param filters
   */
  const handleFilter = (filters) => {
    navigate({ ...filters, [key]: 1 });
  };

  return { handlePageChange, handleSearch, handleFilter };
}
