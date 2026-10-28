import { router } from '@inertiajs/react';
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
import { Minus, Users2Icon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Link } from '@inertiajs/react';
import TablePagination from '@/components/table-pagination';
import { TableSearch } from '@/components/table-search';
import { useTableSearch } from '@/hooks/use-table-search';
import {
  create,
  show,
} from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
//import {create} from '@/routes/cursos-tutelados/classes/turnos/turmas/disciplinas/professores'
//import {create} from '@/actions/App/Http/Controllers/InstituicaoCurso/TurmaDisciplinaProfessorController'

export function TabGruposPAP({
  turma,
  grupos,
  pagination,
  onPageChange,
  search = '',
  can,
  params,
}) {
  const {
    search: searchTerm,
    onChange,
    submit,
    applied,
  } = useTableSearch(search, {
    searchParam: 'search_grupos',
    pageParam: 'page_grupos',
  });
  const isEmpty = grupos.length === 0;

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-start sm:justify-between">
        <div className="min-w-0">
          <CardTitle>Grupos para PAP</CardTitle>
          <CardDescription>
            Grupos de aptidão profissional desta turma
          </CardDescription>
        </div>
        {can?.create && (
          <div className="w-full sm:w-auto">
            <Button asChild className="w-full sm:w-auto">
              <Link
                href={create({
                  instituicao: params.instituicao.id,
                  cursoTutelado: params.cursoTutelado.id,
                  cursoClasse: params.cursoClasse.id,
                  cursoClasseTurno: params.cursoClasseTurno.id,
                  turma: params.turma,
                })}
              >
                Criar grupo
              </Link>
            </Button>
          </div>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        <TableSearch
          value={searchTerm}
          onChange={onChange}
          onSubmit={submit}
          placeholder="Pesquisar por grupo ou tema..."
        />
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users2Icon}
            title={
              applied ? 'Nenhum grupo encontrado' : 'Nenhum grupo criado, ainda'
            }
            description={
              applied
                ? 'Tente ajustar a pesquisa.'
                : 'Comece adicionando grupos'
            }
            action={
              can?.create
                ? {
                    label: 'Criar Grupo',
                    href: create({
                      instituicao: params.instituicao.id,
                      cursoTutelado: params.cursoTutelado.id,
                      cursoClasse: params.cursoClasse.id,
                      cursoClasseTurno: params.cursoClasseTurno.id,
                      turma: params.turma,
                    }),
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Tema</TableHead>
                <TableHead>Estado</TableHead>
                <TableHead className="px-4 text-end">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {grupos.map((grupo) => {
                const canView = Boolean(grupo.can?.view);
                return (
                  <TableRow
                    key={grupo.id}
                    className={canView ? 'hover:cursor-pointer' : 'opacity-70'}
                    aria-disabled={!canView}
                    onClick={() => {
                      if (!canView) {
                        return;
                      }

                      router.visit(
                        show({
                          instituicao: params.instituicao.id,
                          cursoTutelado: params.cursoTutelado.id,
                          cursoClasse: params.cursoClasse.id,
                          cursoClasseTurno: params.cursoClasseTurno.id,
                          turma: params.turma,
                          grupoPap: grupo.id,
                        }),
                      );
                    }}
                  >
                    <TableCell className="px-4 font-medium">
                      {grupo.nome}
                    </TableCell>

                    <TableCell>{grupo.tema}</TableCell>

                    <TableCell>{grupo.status}</TableCell>

                    <TableCell className="text-end">
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
