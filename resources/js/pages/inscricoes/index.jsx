import { router, usePage } from '@inertiajs/react';
import { update } from '@/routes/inscricoes';
import { InscricaoTable } from './components/inscricao-table';

export default function Index() {
  const { inscricoes, anosLectivos, anoLectivoActual, can } = usePage().props;

  const handlePageChange = (page) => {
    router.visit('/dashboard/inscricoes', {
      data: { page, ano_lectivo_id: anoLectivoActual },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (e) => {
    router.visit('/dashboard/inscricoes', {
      data: { ano_lectivo_id: e.target.value },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      {/* Filtro por Ano Lectivo */}
      <div className="mb-6 flex items-center gap-4">
        <label htmlFor="ano-lectivo" className="font-medium">
          Ano Lectivo:
        </label>
        <select
          id="ano-lectivo"
          value={anoLectivoActual || ''}
          onChange={handleAnoLectivoChange}
          className="rounded-md border border-gray-300 px-3 py-2"
        >
          {anosLectivos?.map((ano) => (
            <option key={ano.id} value={ano.id}>
              {ano.nome}
            </option>
          ))}
        </select>
      </div>

      <InscricaoTable
        inscricoes={inscricoes.data}
        can={can}
        pagination={{
          current_page: inscricoes.current_page,
          last_page: inscricoes.last_page,
        }}
        onPageChange={handlePageChange}
        updateFn={(id, nota_teste) =>
          router.patch(update.url(id), { nota_teste })
        }
      />
    </div>
  );
}