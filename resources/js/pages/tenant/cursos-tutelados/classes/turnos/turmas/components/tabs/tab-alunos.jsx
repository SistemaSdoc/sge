import { router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
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

import { Download, Minus, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { show } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { exportarLocal as exportarLista } from '@/actions/App/Http/Controllers/Tenant/Colegios/ExportarListaAlunosTurmaController';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import { ProfileAvatarPreview } from '@/components/profile-avatar-preview';

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
  const isEmpty = alunos.length === 0;

  return (
    <Card className="grid w-full min-w-0 grid-rows-[auto_1fr_auto] gap-0">
      <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle>Alunos</CardTitle>
          <CardDescription>Alunos matriculados nesta turma</CardDescription>
        </div>
        {can.exportar && (
          <div className="w-full sm:w-auto">
            <Button variant="outline" size="sm" className="w-full sm:w-auto" asChild>
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
          </div>
        )}
      </CardHeader>

      <CardContent className="min-w-0 p-0!">
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
          <Table className="min-w-0">
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4 max-md:whitespace-normal">Nome</TableHead>
                <TableHead className="px-4 text-right max-md:whitespace-normal">Acções</TableHead>
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
                    onClick={(event) => {
                      if (
                        event.target instanceof Element
                        && event.target.closest(
                          '[data-profile-avatar-preview], [data-slot="dialog-overlay"]',
                        )
                      ) {
                        return;
                      }

                      if (!canView) {
                        return;
                      }
                      router.visit(show(aluno.id).url);
                    }}
                  >
                    <TableCell className="px-2 font-medium max-md:whitespace-normal sm:px-4">
                      <div className="flex min-w-0 items-center gap-2 sm:gap-3">
                        <ProfileAvatarPreview
                          src={aluno.avatar}
                          name={aluno.nome}
                        />
                        <div className="flex min-w-0 flex-col">
                          <span className="min-w-0 wrap-anywhere text-xs">
                            {aluno?.nome}
                          </span>
                          <span className="text-[10px] text-muted-foreground">
                            Matrícula: {aluno.matricula ?? '—'}
                          </span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell className="px-2 text-right max-md:whitespace-normal sm:px-4">
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
