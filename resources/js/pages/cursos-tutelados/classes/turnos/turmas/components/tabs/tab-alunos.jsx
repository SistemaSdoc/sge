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
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Minus, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { show } from '@/actions/App/Http/Controllers/AlunoController';
import { useCertificado } from '../../hooks/use-certificado';
import TablePagination from '@/components/table-pagination';

export function TabAlunos({
  alunos,
  params,
  pagination,
  onPageChange,
  can = {},
}) {
  const { gerarCertificado } = useCertificado(params);
  const canCreate = Boolean(can.create);
  const isEmpty = alunos.length === 0;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Alunos</CardTitle>
        <CardDescription>Alunos inscritos nesta turma</CardDescription>
        {canCreate && (
          <CardAction>
            <Button>Adicionar</Button>
          </CardAction>
        )}
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum aluno inscrito"
            description="Comece adicionando alunos à turma"
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Matrícula</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Telefone</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {alunos.map((aluno) => {
                return (
                  <TableRow
                    key={aluno.id}
                    className="hover:cursor-pointer"
                    onClick={() => router.visit(show(aluno.id).url)}
                  >
                    <TableCell className="px-4 font-medium">
                      {aluno?.nome}
                    </TableCell>

                    <TableCell>
                      {aluno.matricula ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell>
                      {aluno?.email ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell>
                      {aluno?.telefone ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>

                    <TableCell className="px-4 text-right">
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
