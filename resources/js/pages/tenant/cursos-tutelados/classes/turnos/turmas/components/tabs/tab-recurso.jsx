import { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { Minus, BookIcon, Loader2 } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { toast } from 'sonner';
import TablePagination from '@/components/table-pagination';
import LancamentosRecursoTable from '../../disciplinas/notas/components/lancamentos-recurso-table';
import { index as indexRecurso } from '@/actions/App/Http/Controllers/Tenant/NotaDisciplinaRecursoController';

export function TabRecurso({
  disciplinas = [],
  params,
  pagination,
  onPageChange,
  podeLancarRecurso = true,
}) {
  const [disciplinaSelecionada, setDisciplinaSelecionada] = useState(null);
  const [alunos, setAlunos] = useState([]);
  const [isLoading, setIsLoading] = useState(false);
  const [isPending, setIsPending] = useState(false);

  function handleClick(disciplina) {
    if (!disciplina.professor) {
      toast.warning('Esta disciplina ainda não tem professor atribuído.');
      return;
    }

    setIsLoading(true);
    setDisciplinaSelecionada(disciplina);
    setSheetAberta(true);

    router.visit(
      indexRecurso({ ...params, classeTurnoDisciplina: disciplina.id }).url,
      {
        only: ['alunos'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          setAlunos(page.props.alunos ?? []);
        },
        onError: () => toast.error('Erro ao carregar alunos.'),
        onFinish: () => setIsLoading(false),
      },
    );
  }

  function handleSubmit(payload) {
    if (!disciplinaSelecionada) return;

    setIsPending(true);
    router.post(
      indexRecurso({
        ...params,
        classeTurnoDisciplina: disciplinaSelecionada.id,
      }).url,
      payload,
      {
        onSuccess: () => {
          setSheetAberta(false);
          toast.success('Recurso lançado com sucesso.');
        },
        onError: () => toast.error('Erro ao lançar recurso.'),
        onFinish: () => setIsPending(false),
      },
    );
  }

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Recurso</CardTitle>
        <CardDescription>
          Disciplinas com alunos em situação de recurso
        </CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        {disciplinas.length === 0 ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhuma disciplina"
            description="Não há disciplinas nesta turma."
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Professor</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {disciplinas.map((disciplina) => (
                <TableRow key={disciplina.id} className="hover:cursor-pointer">
                  <TableCell className="px-4 font-medium">
                    {disciplina.nome}
                  </TableCell>
                  <TableCell>
                    {disciplina.professor?.nome ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
