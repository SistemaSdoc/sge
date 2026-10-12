import { router } from '@inertiajs/react';
import {
  Card, CardContent, CardDescription, CardHeader, CardTitle,
} from '@/components/ui/card';
import {
  Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import { Minus, BookIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { toast } from 'sonner';
import TablePagination from '@/components/table-pagination';
import { index as indexRecurso } from '@/actions/App/Http/Controllers/Tenant/NotaDisciplinaRecursoController';

function routeId(value) {
  while (value && typeof value === 'object') {
    value = value.id;
  }

  return value;
}

export function TabRecurso({ disciplinas = [], params, pagination, onPageChange }) {

  function handleClick(disciplina) {
    if (!disciplina.professor) {
      toast.warning('Esta disciplina ainda não tem professor atribuído.');
      return;
    }

    router.visit(
      indexRecurso({
        instituicao: routeId(params.instituicao),
        cursoTutelado: routeId(params.cursoTutelado),
        cursoClasse: routeId(params.cursoClasse),
        cursoClasseTurno: routeId(params.cursoClasseTurno),
        turma: routeId(params.turma),
        classeTurnoDisciplina: routeId(disciplina.classe_turno_disciplina_id),
      }).url,
    );
  }

  return (
    <Card className="gap-0">
      <CardHeader className="flex flex-col gap-1 border-b">
        <div className="min-w-0">
          <CardTitle>Recurso</CardTitle>
          <CardDescription>
            Disciplinas com alunos em situação de recurso
          </CardDescription>
        </div>
      </CardHeader>

      <CardContent className="p-0!">
        {disciplinas.length === 0 ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhuma disciplina"
            description="Não há disciplinas com alunos em recurso."
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
                <TableRow
                  key={disciplina.id}
                  className="hover:cursor-pointer"
                  onClick={() => handleClick(disciplina)}
                >
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