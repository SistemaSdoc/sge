import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Card, CardDescription, CardHeader } from '@/components/ui/card';
import FiltrosPrazos from './components/filtros-prazos';
import CriarPrazoDrawer from './components/criar-prazo-drawer';
import PrazosTable from './components/prazos-table';

export default function Index({ prazos, filters, disciplinas, periodos, classes }) {
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
      router.post(
        `/dashboard/diretor/prazos/${id}/fechar`,
        {},
        { onSuccess: () => router.reload() },
      );
    }
  };

  return (
    <>
      <Head title="Gestão de Prazos de Provas" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        {/* Header + Filtros */}
        <Card className="gap-0! overflow-visible pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <span className="line-clamp-2 text-sm font-semibold text-primary">
                        Direção
                      </span>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Prazos de Provas
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Gere os prazos de submissão de provas por disciplina e classe.
              </CardDescription>
            </div>
          </CardHeader>

          <div className="px-4 py-3">
            <FiltrosPrazos
              filtros={filtros}
              disciplinas={disciplinas}
              onChange={(novos) => {
                setFiltros(novos);
                aplicarFiltros(novos);
              }}
              onLimpar={() => {
                const limpos = {
                  status: '',
                  disciplina_id: '',
                  ano_letivo: '',
                };
                setFiltros(limpos);
                aplicarFiltros(limpos);
              }}
            />
          </div>
        </Card>

        <PrazosTable
          prazos={prazos.data}
          pagination={{
            current_page: prazos.current_page,
            last_page: prazos.last_page,
            total: prazos.total,
          }}
          onPageChange={handlePageChange}
          onFechar={fecharPrazo}
          onCreate={abrirCriarPrazo}
        />
      </div>

      <Dialog open={openCriar} onOpenChange={setOpenCriar}>
        <DialogContent className="max-h-[90vh] w-[95vw] max-w-2xl overflow-y-auto p-4 sm:w-full sm:p-6">
          <DialogHeader>
            <DialogTitle>Novo Prazo de Provas</DialogTitle>
          </DialogHeader>
          {openCriar && (
            <CriarPrazoDrawer
              disciplinas={disciplinas}
              periodos={periodos}
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