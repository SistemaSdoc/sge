// resources/js/pages/tenant/relatorio/index.jsx
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';

import ResumoGeral from './resumo-geral';

import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Input } from '@/components/ui/input';

const TIPOS = [
  { value: 'geral', label: 'Resumo geral' },
  { value: 'alunos', label: 'Alunos' },
  { value: 'professores', label: 'Professores' },
  { value: 'turmas', label: 'Turmas' },
  { value: 'pap', label: 'PAP' },
  { value: 'financeiro', label: 'Financeiro' },
];

export default function RelatorioIndex({
  tipo,
  filtros,
  stats,
  tabela,
  graficos,
  kpis_extra: kpisExtra,
  turmas,
  classes,
  anosLectivos,
}) {
  const [pesquisa, setPesquisa] = useState(filtros?.pesquisa ?? '');

  function irPara(novosFiltros) {
    router.get(route('relatorios.index'), { ...filtros, ...novosFiltros }, {
      preserveState: true,
      preserveScroll: true,
      replace: true,
    });
  }

  function handleTipoChange(novoTipo) {
    irPara({ tipo: novoTipo });
  }

  function handlePesquisaSubmit(e) {
    e.preventDefault();
    irPara({ pesquisa });
  }

  return (
    <>
      <Head title="Relatórios" />

      <div className="flex h-full flex-1 flex-col gap-6 overflow-x-auto rounded-xl p-4">
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <h1 className="font-heading text-xl font-semibold text-foreground">Relatórios</h1>

          <div className="flex flex-wrap items-center gap-2">
            <Select value={tipo} onValueChange={handleTipoChange}>
              <SelectTrigger className="w-48">
                <SelectValue placeholder="Tipo de relatório" />
              </SelectTrigger>
              <SelectContent>
                {TIPOS.map((t) => (
                  <SelectItem key={t.value} value={t.value}>
                    {t.label}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            <Select
              value={filtros?.ano_lectivo_id ? String(filtros.ano_lectivo_id) : ''}
              onValueChange={(v) => irPara({ ano_lectivo_id: v })}
            >
              <SelectTrigger className="w-48">
                <SelectValue placeholder="Ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos?.map((a) => (
                  <SelectItem key={a.id} value={String(a.id)}>
                    {a.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>

            {tipo !== 'geral' && (
              <form onSubmit={handlePesquisaSubmit}>
                <Input
                  value={pesquisa}
                  onChange={(e) => setPesquisa(e.target.value)}
                  placeholder="Pesquisar..."
                  className="w-48"
                />
              </form>
            )}
          </div>
        </div>

        {tipo === 'geral' ? (
          <ResumoGeral stats={stats} graficos={graficos} kpis_extra={kpisExtra} />
        ) : (
          <pre className="overflow-auto rounded-none bg-muted p-4 text-xs">
            {JSON.stringify({ stats, tabela }, null, 2)}
          </pre>
        )}
      </div>
    </>
  );
}

RelatorioIndex.layout = {
  breadcrumbs: [
    {
      title: 'Relatórios',
      href: '/relatorios',
    },
  ],
};