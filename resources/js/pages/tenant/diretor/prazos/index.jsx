import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
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
import PrazosTable from './components/prazos-table';

const normalizar = (v) => {
  if (!v || v === 'todos' || v === 'todas') return '';
  return v;
};

const buildQuery = (obj) => {
  const params = new URLSearchParams();
  Object.keys(obj).forEach((key) => {
    const v = obj[key];
    if (v && v !== 'todos' && v !== 'todas') {
      params.append(key, v);
    }
  });
  const qs = params.toString();
  return qs ? `?${qs}` : '';
};

export default function Index({ prazos, filters, disciplinas }) {
  const [filtros, setFiltros] = useState({
    status: normalizar(filters?.status),
    disciplina_id: normalizar(filters?.disciplina_id),
    ano_letivo: normalizar(filters?.ano_letivo),
  });

  const aplicarFiltros = (novosFiltros) => {
    router.visit(
      '/dashboard/diretor/prazos' + buildQuery(novosFiltros),
      { preserveScroll: true },
    );
  };

  const handlePageChange = (page) => {
    router.visit(
      '/dashboard/diretor/prazos' + buildQuery({ page, ...filtros }),
      { preserveScroll: true },
    );
  };

  // Agora navega para a página create em vez de abrir modal
  const abrirCriarPrazo = () => {
    router.visit('/dashboard/diretor/prazos/create');
  };

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
    </>
  );
}