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
import { show as showAluno } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { show as showUser } from '@/actions/App/Http/Controllers/Tenant/UserProfileController';
import { create as adicionarElemento } from '@/actions/App/Http/Controllers/Tenant/ElementoGrupoPapController';
import TablePagination from '@/components/table-pagination';
import { ProfileAvatarPreview } from '@/components/profile-avatar-preview';

export function TabIntegrantes({
  params,
  grupoPap,
  notas,
  setNotas,
  actualizarNotaFn,
  removerIntegranteFn,
  pagination,
  onPageChange,
  can,
  currentUserId,
}) {
  const [editando, setEditando] = useState({});
  const elementos = pagination?.data ?? [];
  const isEmpty = elementos.length === 0;
  const canCreateIntegrante = Boolean(can?.elementos?.create);
  const canAtualizarNota = Boolean(can?.elementos?.atualizarNota);
  const canRemoverIntegrante = Boolean(can?.elementos?.delete);
  const hasActionsColumn = canAtualizarNota || canRemoverIntegrante;

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
      <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle>Integrantes do grupo</CardTitle>
          <CardDescription>Alunos membros e notas individuais</CardDescription>
        </div>
        {canCreateIntegrante && (
          <div className="w-full sm:w-auto">
            <Button className="w-full sm:w-auto" asChild>
              <Link href={adicionarElemento.url(params)}>Adicionar</Link>
            </Button>
          </div>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum integrante no grupo"
            description="Comece adicionando os primeiros membros do grupo PAP"
            action={
              canCreateIntegrante
                ? {
                    label: 'Adicionar Integrante',
                    href: adicionarElemento.url(params),
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
                <TableHead className='text-center'>Nota individual</TableHead>
                {hasActionsColumn && (
                  <TableHead className="px-4 text-right">Acções</TableHead>
                )}
              </TableRow>
            </TableHeader>
            <TableBody>
              {elementos.map((el) => (
                <TableRow
                  key={el.id}
                  className={el.can_view ? 'hover:cursor-pointer' : undefined}
                  onClick={() =>
                    el.can_view &&
                    router.visit(
                      String(currentUserId) === String(el.user_id)
                        ? showUser.url({ user: el.user_id })
                        : showAluno.url({ aluno: el.aluno_id }),
                    )
                  }
                >
                  <TableCell className="px-4 font-medium">
                    <div className="flex items-center gap-3">
                      <ProfileAvatarPreview src={el.avatar} name={el.nome} />
                      <div className="flex min-w-0 flex-col">
                        <span className="min-w-0 wrap-anywhere">{el.nome}</span>
                        <span className="text-[10px] text-muted-foreground">
                          Matrícula: {el.matricula ?? '—'}
                        </span>
                      </div>
                    </div>
                  </TableCell>

                  <TableCell onClick={(e) => e.stopPropagation()}>
                    {canAtualizarNota && !notaJaLancada(el) ? (
                      <div className="flex items-center gap-2">
                        <Input
                          type="number"
                          min="0"
                          max="20"
                          step="0.5"
                          className="w-20"
                          defaultValue={
                            el.nota_individual != null
                              ? Number(el.nota_individual)
                              : ''
                          }
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
                    ) : (
                        el.nota_individual != null ? (
                          <span className="text-sm font-medium tabular-nums">
                            {Number(el.nota_individual) % 1 === 0
                            ? Number(el.nota_individual).toFixed(0)
                              : Number(el.nota_individual)}
                          </span>
                        ) : (
                          <div className="flex justify-center">
                            <Minus size={15} className="text-muted-foreground" />
                          </div>
                        )
                    )}
                  </TableCell>

                  {hasActionsColumn && (
                    <TableCell
                      className="px-4 text-right"
                      onClick={(e) => e.stopPropagation()}
                    >
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                          >
                            <MoreHorizontalIcon />
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end">
                          {canAtualizarNota && (
                            <DropdownMenuItem
                              onClick={() =>
                                setEditando((prev) => ({
                                  ...prev,
                                  [el.id]: true,
                                }))
                              }
                            >
                              Editar nota
                            </DropdownMenuItem>
                          )}

                          {canRemoverIntegrante && (
                            <DropdownMenuItem
                              variant="destructive"
                              onClick={() => removerIntegranteFn(el)}
                            >
                              Remover do grupo
                            </DropdownMenuItem>
                          )}
                        </DropdownMenuContent>
                      </DropdownMenu>
                    </TableCell>
                  )}
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
      <TablePagination
        pagination={pagination?.meta}
        onPageChange={onPageChange}
      />
    </Card>
  );
}
