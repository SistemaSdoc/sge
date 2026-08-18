import { router, usePage } from '@inertiajs/react';
import { update, destroy, reativar } from '@/routes/inscricoes'; // ← verifica o nome exportado
import { InscricaoTable } from './components/inscricao-table';
import { useDialog } from '@/hooks/use-dialog';

export default function Index() {
  const {
    inscricoes,
    anosLectivos,
    anoLectivoActual,
    can,
    entity_label: entityLabel,
    entity_label_plural: entityLabelPlural,
    tem_nota_teste: temNotaTeste,
  } = usePage().props;

  const handlePageChange = (page) => {
    router.visit('/dashboard/inscricoes', {
      data: { page, ano_lectivo_id: anoLectivoActual },
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit('/dashboard/inscricoes', {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  const { confirm } = useDialog();

const handleReativarInscricao = (id) => {
  confirm({
    title: 'Reativar inscrição',
    description: 'Esta inscrição voltará ao estado anterior.',
    confirmLabel: 'Reativar',
    confirmFn: () =>
      router.patch(reativar.url(id), {
        preserveScroll: true,
      }),
  });
};

  const handleCancelarInscricao = (id) => {
    confirm({
      title: 'Tens a certeza?',
      description: 'Esta matrícula será cancelada.',
      confirmLabel: 'Cancelar Matrícula',
      confirmFn: () =>
        router.delete(destroy.url(id), {
          preserveScroll: true,
        }),
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
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
        destroyFn={handleCancelarInscricao}
        reativarFn={handleReativarInscricao}
        anoLectivoActual={anoLectivoActual}
        anosLectivos={anosLectivos}
        onAnoLectivoChange={handleAnoLectivoChange}
        entityLabel={entityLabel}
        entityLabelPlural={entityLabelPlural}
        temNotaTeste={temNotaTeste}
      />
    </div>
  );
}
