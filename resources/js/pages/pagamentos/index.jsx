import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import PagamentosTable from './components/pagamentos-table';
import AlunosPorStatusPropina from './components/alunos-por-status-propina';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  index,
  destroy,
} from '@/actions/App/Http/Controllers/PagamentoController';
import { porTurma } from '@/actions/App/Http/Controllers/RelatorioPropinaController';
import { AlertTriangleIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Popover,
  PopoverContent,
  PopoverTrigger,
} from '@/components/ui/popover';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Label } from '@/components/ui/label';

<<<<<<< HEAD
export default function Index({ pagamentos, can, turmas = [] }) {
=======
export default function Index({ pagamentos, can, statusFiltro, alunosPorStatus }) {
>>>>>>> 6e1a31c (Filtro de pagamentos de propina)
  const { deleteConfirm } = useDialog();
  const [turmaEscolhida, setTurmaEscolhida] = useState('');
  const [popoverAberto, setPopoverAberto] = useState(false);

  const handleDelete = (id) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O pagamento será removido permanentemente.',
      confirmLabel: 'Eliminar',
      confirmFn: () => router.delete(destroy(id).url),
    });
  };

  const handlePageChange = (page) => {
    router.visit(index().url, { data: { page }, preserveScroll: true });
  };

<<<<<<< HEAD
  const verRelatorio = () => {
    if (!turmaEscolhida) return;
    router.visit(porTurma(turmaEscolhida).url);
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      <Head title="Pagamentos" />

      <div className="flex justify-end">
        <Popover open={popoverAberto} onOpenChange={setPopoverAberto}>
          <PopoverTrigger asChild>
            <Button variant="outline" size="sm">
              <AlertTriangleIcon className="mr-1 size-4" />
              Alunos Devedores
            </Button>
          </PopoverTrigger>

          <PopoverContent align="end" className="w-72 space-y-3">
            <div className="space-y-1">
              <Label htmlFor="turma_id">Escolher turma</Label>
              <Select value={turmaEscolhida} onValueChange={setTurmaEscolhida}>
                <SelectTrigger id="turma_id" className="w-full">
                  <SelectValue placeholder="Selecionar turma" />
                </SelectTrigger>
                <SelectContent>
                  {turmas.map((t) => (
                    <SelectItem key={t.id} value={t.id}>
                      {t.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <div className="flex justify-end">
              <Button
                size="sm"
                disabled={!turmaEscolhida}
                onClick={verRelatorio}
              >
                Ver relatório
              </Button>
            </div>
          </PopoverContent>
        </Popover>
      </div>

      <PagamentosTable
        pagamentos={pagamentos?.data ?? []}
        can={can}
        deleteFn={handleDelete}
        pagination={{
          current_page: pagamentos.current_page,
          last_page: pagamentos.last_page,
        }}
        onPageChange={handlePageChange}
      />
=======
 const handleStatusChange = (value) => {
  router.visit(index().url, {
    data: { status_propina: value === 'todos' ? undefined : value },
    preserveScroll: true,
    preserveState: false, // deixa o Inertia substituir as props inteiras vindas do server
    replace: true,        // evita empilhar entradas de histórico com estados antigos
  });
};

  return (
    <div className="mx-auto w-full max-w-7xl p-6 space-y-6">
      <Head title="Pagamentos" />

      <div className="flex items-center gap-3">
        <span className="text-sm font-medium">Lista de Pagamentos</span>
        <Select value={statusFiltro ?? 'todos'} onValueChange={handleStatusChange}>
          <SelectTrigger className="w-56">
            <SelectValue placeholder="Selecciona um filtro" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem value="todos">Registro de pagamento</SelectItem>
            <SelectItem value="pagos">Pagos</SelectItem>
            <SelectItem value="nao_pagos">Não Pagos</SelectItem>
          </SelectContent>
        </Select>
      </div>

      {statusFiltro && alunosPorStatus ? (
        <AlunosPorStatusPropina
          statusFiltro={statusFiltro}
          alunosPorStatus={alunosPorStatus}
        />
      ) : (
        <PagamentosTable
          can={can}
          pagamentos={pagamentos?.data ?? []}
          deleteFn={handleDelete}
          pagination={{
            current_page: pagamentos.current_page,
            last_page: pagamentos.last_page,
          }}
          onPageChange={handlePageChange}
        />
      )}
>>>>>>> 6e1a31c (Filtro de pagamentos de propina)
    </div>
  );
}