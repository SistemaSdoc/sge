import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import FiltrosPrazos from './components/filtros-prazos';
import CriarPrazoDrawer from './components/criar-prazo-drawer';
import PrazosTable from './components/prazos-table';

export default function Index({ prazos, filters, disciplinas, classes }) {
  const [openCriar, setOpenCriar] = useState(false);

  const [filtros, setFiltros] = useState({
    status: filters?.status || '',
    disciplina_id: filters?.disciplina_id || '',
    ano_letivo: filters?.ano_letivo || '',
  });

  const aplicarFiltros = (novosFiltros) => {
    const params = new URLSearchParams();
    Object.keys(novosFiltros).forEach((key) => {
      if (novosFiltros[key]) params.append(key, novosFiltros[key]);
    });
    router.visit('/dashboard/diretor/prazos?' + params.toString(), {
      preserveScroll: true,
    });
  };

  const handlePageChange = (page) => {
    router.visit('/dashboard/diretor/prazos', {
      data: { page, ...filtros },
      preserveScroll: true,
    });
  };

  const abrirCriarPrazo = () => setOpenCriar(true);
  const fecharCriarPrazo = () => setOpenCriar(false);

  const fecharPrazo = (id) => {
    if (confirm('Tem certeza que deseja encerrar este prazo?')) {
      router.post(`/dashboard/diretor/prazos/${id}/fechar`, {}, {
        onSuccess: () => router.reload(),
      });
    }
  };

  return (
    <>
      <Head title="Gestão de Prazos de Provas" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
        <div className="bg-card p-4  border border-border shadow-sm">
          <FiltrosPrazos
            filtros={filtros}
            disciplinas={disciplinas}
            onChange={(novos) => {
              setFiltros(novos);
              aplicarFiltros(novos);
            }}
            onLimpar={() => {
              setFiltros({ status: '', disciplina_id: '', ano_letivo: '' });
              aplicarFiltros({ status: '', disciplina_id: '', ano_letivo: '' });
            }}
          />
        </div>

        <PrazosTable
          prazos={prazos.data}
          pagination={{
            current_page: prazos.current_page,
            last_page: prazos.last_page,
          }}
          onPageChange={handlePageChange}
          onFechar={fecharPrazo}
          onCreate={abrirCriarPrazo}
        />
      </div>

{/*  MODAL CENTRALIZADO */}
<Dialog open={openCriar} onOpenChange={setOpenCriar}>
  <DialogContent className="w-[95vw] max-w-2xl sm:w-full max-h-[90vh] overflow-y-auto p-4 sm:p-6">
    <DialogHeader>
      <DialogTitle>Novo Prazo de Provas</DialogTitle>
    </DialogHeader>
    {openCriar && (
      <CriarPrazoDrawer
        disciplinas={disciplinas}
        classes={classes}
        onSuccess={fecharCriarPrazo}
        onCancel={fecharCriarPrazo}
      />
    )}
  </DialogContent>
</Dialog>
    </>
  );
}