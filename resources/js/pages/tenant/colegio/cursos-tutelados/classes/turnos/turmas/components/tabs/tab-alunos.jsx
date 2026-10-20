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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Download, Minus, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { show } from '@/actions/App/Http/Controllers/Tenant/AlunoController';
import { exportar as exportarLista } from '@/actions/App/Http/Controllers/Tenant/Colegios/ExportarListaAlunosTurmaController';
import { useCertificado } from '../../hooks/use-certificado';
import TablePagination from '@/components/table-pagination';
import { ProfileAvatarPreview } from '@/components/profile-avatar-preview';

export function TabAlunos({
  alunos,
  params,
  pagination,
  onPageChange,
  can = {},
}) {
  const { gerarCertificado } = useCertificado(params);
  const isCoordenador = Boolean(can.coordenador);
  const isEmpty = alunos.length === 0;

  return (
    <Card className="w-full min-w-0 gap-0">
      <CardHeader className="flex flex-col gap-3 border-b sm:flex-row sm:items-center sm:justify-between">
        <div>
          <CardTitle>Alunos</CardTitle>
          <CardDescription>Alunos inscritos nesta turma</CardDescription>
        </div>
        {can.exportar && (
          <div className="w-full sm:w-auto">
            <Button
              variant="outline"
              size="sm"
              className="w-full sm:w-auto"
              asChild
            >
              <a href={exportarLista(params).url}>
                <Download className="size-4" />
                Exportar lista
              </a>
            </Button>
          </div>
        )}
      </CardHeader>

      <CardContent className="min-w-0 p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum aluno inscrito"
            description="Comece adicionando alunos à turma"
          />
        ) : (
          <Table className="min-w-0">
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-2 max-md:whitespace-normal sm:px-4">
                  Nome
                </TableHead>
                <TableHead className="max-md:whitespace-normal">
                  Email
                </TableHead>
                <TableHead className="max-md:whitespace-normal">
                  Telefone
                </TableHead>
                <TableHead className="px-2 text-right max-md:whitespace-normal sm:px-4">
                  Acções
                </TableHead>
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
                        event.target instanceof Element &&
                        event.target.closest(
                          '[data-profile-avatar-preview], [data-slot="dialog-overlay"]',
                        )
                      ) {
                        return;
                      }

                      if (canView) {
                        router.visit(show(aluno.id).url);
                      }
                    }}
                  >
                    <TableCell className="px-2 font-medium max-md:whitespace-normal sm:px-4">
                      <div className="flex min-w-0 items-center gap-2 sm:gap-3">
                        <ProfileAvatarPreview
                          src={aluno.avatar}
                          name={aluno.nome}
                        />
                        <div className="flex min-w-0 flex-col">
                          <span className="min-w-0 text-xs wrap-anywhere">
                            {aluno?.nome}
                          </span>
                          <span className="text-[10px] text-muted-foreground">
                            Matrícula: {aluno.matricula ?? '—'}
                          </span>
                        </div>
                      </div>
                    </TableCell>

                    <TableCell className="wrap-anywhere max-md:whitespace-normal">
                      {aluno?.email ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell className="wrap-anywhere max-md:whitespace-normal">
                      {aluno?.telefone ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell className="px-2 text-right max-md:whitespace-normal sm:px-4">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button variant="ghost" size="icon">
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>

                        <DropdownMenuContent align="end" className="w-auto">
                          <DropdownMenuItem
                            onClick={(e) => gerarCertificado(e, aluno.id)}
                          >
                            Gerar Certificado
                          </DropdownMenuItem>
                        </DropdownMenuContent>
                      </DropdownMenu>
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
