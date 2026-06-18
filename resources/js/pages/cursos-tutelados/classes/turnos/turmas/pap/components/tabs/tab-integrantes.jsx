import { Link, router } from '@inertiajs/react';
import { useState } from 'react';
import { Minus, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { Input } from '@/components/ui/input';
import { Button } from '@/components/ui/button';
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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { EmptyState } from '@/components/empty-state';
import { show as showAluno } from '@/actions/App/Http/Controllers/AlunoController';
import { create as adicionarElemento } from '@/actions/App/Http/Controllers/ElementoGrupoPapController';

export function TabIntegrantes({
  params,
  grupoPap,
  notas,
  setNotas,
  actualizarNotaFn,
  removerIntegranteFn,
}) {
  const isEmpty = !grupoPap?.elementos || grupoPap.elementos.length === 0;
  const [editando, setEditando] = useState({});

  function handleSalvar(el) {
    actualizarNotaFn(
      {
        elementoId: el.id,
        data: { nota_individual: Number(notas[el.id] ?? el.nota_individual) },
      },
      {
        onSuccess: () => setEditando((prev) => ({ ...prev, [el.id]: false })),
      },
    );
  }

  function notaJaLancada(el) {
    return (
      el.nota_individual !== null &&
      el.nota_individual !== undefined &&
      !editando[el.id]
    );
  }

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Integrantes do grupo</CardTitle>
        <CardDescription>Alunos membros e notas individuais</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={adicionarElemento.url(params)}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum integrante no grupo"
            description="Comece adicionando os primeiros membros do grupo PAP"
            action={{
              label: 'Adicionar Integrante',
              href: adicionarElemento.url(params),
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Matrícula</TableHead>
                <TableHead>Nota individual</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {grupoPap?.elementos.map((el) => (
                <TableRow
                  key={el.id}
                  className="hover:cursor-pointer"
                  onClick={() =>
                    router.visit(showAluno.url({ aluno: el.aluno_id }))
                  }
                >
                  <TableCell className="px-4 font-medium">{el.nome}</TableCell>
                  <TableCell>
                    {el.matricula ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell onClick={(e) => e.stopPropagation()}>
                    {notaJaLancada(el) ? (
                      <span className="text-sm font-medium tabular-nums">
                        {Number(el.nota_individual)}
                      </span>
                    ) : (
                      <div className="flex items-center gap-2">
                        <Input
                          type="number"
                          min="0"
                          max="20"
                          step="0.5"
                          className="w-20"
                          defaultValue={el.nota_individual ?? ''}
                          onChange={(e) =>
                            setNotas((prev) => ({
                              ...prev,
                              [el.id]: e.target.value,
                            }))
                          }
                        />
                        <Button
                          size="sm"
                          variant="outline"
                          onClick={() => handleSalvar(el)}
                        >
                          Salvar
                        </Button>
                      </div>
                    )}
                  </TableCell>

                  <TableCell
                    className="px-4 text-right"
                    onClick={(e) => e.stopPropagation()}
                  >
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={() =>
                            setEditando((prev) => ({ ...prev, [el.id]: true }))
                          }
                        >
                          Editar nota
                        </DropdownMenuItem>

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={() => removerIntegranteFn(el.id)}
                        >
                          Remover do grupo
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  );
}
