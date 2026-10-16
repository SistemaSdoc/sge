import { router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
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

import { Download, Minus, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { show } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { exportarLocal as exportarLista } from '@/actions/App/Http/Controllers/Tenant/Colegios/ExportarListaAlunosTurmaController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';

export function TabAlunos({
  alunos,
  params,
  pagination,
  onPageChange,
  search = '',
  can = {},
}) {
  const {
    search: searchTerm,
    onChange,
    submit,
    applied,
  } = useTableSearch(search, {
    searchParam: 'search_alunos',
    pageParam: 'page_alunos',
  });
  const canCreate = Boolean(can.create);
  const isEmpty = alunos.length === 0;

  return (
    <Card className="grid grid-rows-[auto_1fr_auto] gap-0">
      <CardHeader className="border-b">
        <CardTitle>Alunos</CardTitle>
        <CardDescription>Alunos matriculados nesta turma</CardDescription>
        {can.exportar && (
          <CardAction>
            <Button variant="outline" size="sm" asChild>
              <a
                href={
                  exportarLista({
                    instituicao: params.instituicao?.id ?? params.instituicao,
                    cursoTutelado:
                      params.cursoTutelado?.id ?? params.cursoTutelado,
                    cursoClasse: params.cursoClasse?.id ?? params.cursoClasse,
                    cursoClasseTurno:
                      params.cursoClasseTurno?.id ?? params.cursoClasseTurno,
                    turma: params.turma?.id ?? params.turma,
                  }).url
                }
              >
                <Download className="size-4" />
                Exportar lista
              </a>
            </Button>
          </CardAction>
        )}
        {/* {canCreate && (
          <CardAction>
            <Button>Adicionar Alunos</Button>
          </CardAction>
        )}  */}
      </CardHeader>

      <CardContent className="p-0!">
        <TableSearch
          value={searchTerm}
          onChange={onChange}
          onSubmit={submit}
          placeholder="Pesquisar por nome ou matrícula..."
        />
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title={
              applied ? 'Nenhum aluno encontrado' : 'Nenhum aluno adicionado'
            }
            description={
              applied
                ? 'Tente ajustar a pesquisa.'
                : 'Comece adicionando alunos à turma'
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead className="text-center">Matrícula</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {alunos.map((aluno) => {
                const canView = Boolean(aluno.can?.view);
                return (
                  <TableRow
                    key={aluno.id}
                    className={canView ? 'hover:cursor-pointer' : 'opacity-70'}
                    aria-disabled={!canView}
                    onClick={() => {
                      if (!canView) {
                        return;
                      }
                      router.visit(show(aluno.id).url);
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {aluno?.nome}
                    </TableCell>

                    <TableCell className="text-center">
                      {aluno.matricula ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell className="px-4 text-right">
                      <Button
                        variant="outline"
                        size="xs"
                        className="text-[10px]"
                        disabled={!canView}
                      >
                        Ver detalhes
                      </Button>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
