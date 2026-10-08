// resources/js/hooks/use-table-search.js
import { router } from '@inertiajs/react';
import { useState } from 'react';

export function useTableSearch(initial = '', { only } = {}) {
  const [search, setSearch] = useState(initial ?? '');

  // termo já aplicado (o que está na URL)
  const [applied, setApplied] = useState(() =>
    typeof window === 'undefined'
      ? (initial ?? '')
      : (new URLSearchParams(window.location.search).get('search') ?? ''),
  );

  const run = (value) => {
    const params = Object.fromEntries(
      new URLSearchParams(window.location.search),
    );
    const term = value.trim();

    delete params.page;

    if (term) {
      params.search = term;
    } else {
      delete params.search;
    }

    setApplied(term);

    router.get(window.location.pathname, params, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
      only,
    });
  };

  const submit = () => run(search);

  const onChange = (value) => {
    setSearch(value);

    // apagou tudo e havia pesquisa aplicada: volta a listar tudo
    if (value === '' && applied) {
      run('');
    }
  };

  return { search, onChange, submit, applied };
}
