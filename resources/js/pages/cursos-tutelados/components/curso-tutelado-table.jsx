import { Link, router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Minus, BookIcon, Search } from 'lucide-react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';

import { EmptyState } from '@/components/empty-state';
import {
  create,
  show,
  edit,
} from '@/actions/App/Http/Controllers/CursoTuteladoController';
import TablePagination from '@/components/table-pagination';
import { ButtonGroup } from '@/components/ui/button-group';

export function CursosTuteladosTable({
  data,
  instituicaoId,
  deleteFn,
  pagination = {},
  onPageChange,
  can = {},
}) {
  const [filtroNome, setFiltroNome] = useState('');

  const isEmpty = !data || data.length === 0;
  const canCreate = Boolean(can?.create_curso || can?.create);

  // Filtrar dados
  const linhasFiltradas = data.filter((curso) =>
    curso.nome.toLowerCase().includes(filtroNome.toLowerCase()),
  );

  return (
    <Card className="gap-0">
      {/* Header */}
      <CardHeader className="border-b">
        <div className="flex items-start justify-between">
          <div>
            <CardTitle>Cursos</CardTitle>
            <CardDescription>
              Cursos lecionados por esta instituição
            </CardDescription>
          </div>
          {canCreate && (
            <CardAction>
              <Button asChild>
                <Link href={create(instituicaoId).url}>Adicionar Curso</Link>
              </Button>
            </CardAction>
          )}
        </div>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookIcon}
            title="Nenhum curso cadastrado"
            description="Comece adicionando o primeiro curso à instituição"
            action={
              canCreate
                ? {
                    label: 'Adicionar Curso',
                    href: create(instituicaoId).url,
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <>
            {/* Filtros */}
            <div className="border-b bg-muted/30 px-4 py-3">
              <div className="flex justify-end">
                <ButtonGroup className="w-full max-w-xs">
                  <Input type="search" placeholder="Pesquisar..." />

                  <Button variant="outline" size="icon">
                    <Search />
                    <span className="sr-only">Pesquisar</span>
                  </Button>
                </ButtonGroup>
              </div>
            </div>

            {/* Tabela */}
            {linhasFiltradas.length === 0 ? (
              <EmptyState
                variant="table"
                icon={BookIcon}
                title="Nenhum curso encontrado"
                description="Tenta ajustar os filtros"
              />
            ) : (
              <Table>
                <TableHeader>
                  <TableRow className="bg-muted/72">
                    <TableHead className="px-4">Nome</TableHead>
                    <TableHead>Tutelado por</TableHead>
                    <TableHead className="px-4 text-right">Acções</TableHead>
                  </TableRow>
                </TableHeader>
                <TableBody>
                  {linhasFiltradas.map((curso) => (
                    <TableRow
                      key={curso.id}
                      className={
                        curso.can?.view ? 'hover:cursor-pointer' : 'opacity-70'
                      }
                      aria-disabled={!curso.can?.view}
                      onClick={() => {
                        if (curso.can?.view) {
                          router.visit(
                            show({
                              instituicao: instituicaoId,
                              cursoTutelado: curso?.id,
                            }).url,
                          );
                        }
                      }}
                    >
                      <TableCell className="px-4 font-medium">
                        {curso.nome}
                      </TableCell>
                      <TableCell>
                        {curso.instituicao_tutora ? (
                          curso.instituicao_tutora
                        ) : (
                          <Minus size={15} className="text-muted-foreground" />
                        )}
                      </TableCell>
                      <TableCell className="px-4 text-right">
                        <div className="flex items-center justify-end gap-2">
                          {/* Botão Editar */}
                          {curso.can?.update && (
                            <Button
                              variant="outline"
                              size="xs"
                              onClick={(e) => {
                                e.stopPropagation();
                                router.visit(
                                  edit({
                                    instituicao: instituicaoId,
                                    cursoTutelado: curso.id,
                                  }).url,
                                );
                              }}
                            >
                              <span className="hidden sm:inline">Editar</span>
                            </Button>
                          )}

                          {/* Botão Remover */}
                          {curso.can?.delete && (
                            <Button
                              variant="destructive"
                              size="xs"
                              onClick={(e) => {
                                e.stopPropagation();
                                deleteFn(curso.id);
                              }}
                            >
                              <span className="hidden sm:inline">Remover</span>
                            </Button>
                          )}
                        </div>
                      </TableCell>
                    </TableRow>
                  ))}
                </TableBody>
              </Table>
            )}
          </>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}
