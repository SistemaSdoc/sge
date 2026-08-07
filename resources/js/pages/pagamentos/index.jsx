import { useDialog } from '@/hooks/use-dialog';
import { Head, router } from '@inertiajs/react';
import { useState } from 'react';
import { FileTextIcon, DownloadIcon } from 'lucide-react';
import PagamentosTable from './components/pagamentos-table';
import AlunosPorStatusPropina from './components/alunos-por-status-propina';
import { Button } from '@/components/ui/button';
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
import {
  porTurma,
  pdf as pdfRelatorioTurma,
} from '@/actions/App/Http/Controllers/RelatorioPropinaController';

export default function Index({
  pagamentos,
  turmas,
  can,
  statusFiltro,
  alunosPorStatus,
}) {
  const { deleteConfirm } = useDialog();
  const [turmaEscolhida, setTurmaEscolhida] = useState('');

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
      preserveState: false,
      replace: true,
    });
  };

  const handleVerRelatorio = () => {
    if (!turmaEscolhida) return;
    router.visit(porTurma(turmaEscolhida).url);
  };

  const handleBaixarPdf = () => {
    if (!turmaEscolhida) return;
    window.open(pdfRelatorioTurma(turmaEscolhida).url, '_blank');
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <Head title="Pagamentos" />

      {/* Relatório de propinas por turma (devedores vs em dia) */}
      <div className="flex flex-wrap items-center gap-3 rounded-lg border bg-muted/30 p-3">
        <span className="text-sm font-medium">Relatório por turma</span>

        <Select value={turmaEscolhida} onValueChange={setTurmaEscolhida}>
          <SelectTrigger className="w-64">
            <SelectValue placeholder="Escolhe uma turma" />
          </SelectTrigger>
          <SelectContent>
            {(turmas ?? []).map((t) => (
              <SelectItem key={t.id} value={t.id}>
                {t.nome}
              </SelectItem>
            ))}
          </SelectContent>
        </Select>

        <Button
          variant="secondary"
          size="sm"
          disabled={!turmaEscolhida}
          onClick={handleVerRelatorio}
        >
          <FileTextIcon className="mr-1.5 size-4" />
          Ver relatório
        </Button>

        <Button
          variant="outline"
          size="sm"
          disabled={!turmaEscolhida}
          onClick={handleBaixarPdf}
        >
          <DownloadIcon className="mr-1.5 size-4" />
          Baixar PDF
        </Button>
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