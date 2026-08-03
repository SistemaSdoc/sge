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


export default function Index({ pagamentos, can, statusFiltro, alunosPorStatus }) {
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
    </div>
  );
}