import { useState } from 'react';

export function usePesquisaAlunos() {
  const [resultados, setResultados] = useState([]);
  const [searching, setSearching] = useState(false);
  const [notFound, setNotFound] = useState(false);
  const [queryActual, setQueryActual] = useState('');

  async function pesquisar(query, subtipo = null) {
    const q = query.trim();

    setQueryActual(q);
    setResultados([]);
    setNotFound(false);

    if (q.length < 1) {
      return;
    }

    setSearching(true);

    try {
      const params = new URLSearchParams({ q });

      if (subtipo) {
        params.set('subtipo', subtipo);
      }

      const res = await fetch(
        `/dashboard/documentos/pesquisar-aluno?${params}`,
        {
          headers: {
            Accept: 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
        },
      );

      if (!res.ok) {
        setResultados([]);
        setNotFound(true);

        return;
      }

      const data = await res.json();

      // Rejeita objectos vazios ou sem id
      const lista = Array.isArray(data)
        ? data.filter((a) => a?.id)
        : data?.id
          ? [data]
          : [];

      setResultados(lista);
      setNotFound(lista.length === 0);
    } catch {
      setResultados([]);
      setNotFound(true);
    } finally {
      setSearching(false);
    }
  }

  function limpar() {
    setResultados([]);
    setNotFound(false);
    setQueryActual('');
    setSearching(false);
  }

  return {
    resultados,
    searching,
    notFound,
    queryActual,
    pesquisar,
    limpar,
  };
}
