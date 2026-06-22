import { router, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
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
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from '@/components/ui/pagination';
import { Minus, MoreHorizontalIcon, UsersIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { show } from '@/actions/App/Http/Controllers/AlunoController';
import { gerar } from '@/actions/App/Http/Controllers/CertificadoController';

export function TabAlunos({
  turma,
  instituicaoId,
  cursoTuteladoId,
  cursoClasseId,
  cursoClasseTurnoId,
  pagination, // ← NOVO: recebe paginação
}) {
  const turmaId = turma.id;
  const baseUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/classes/${cursoClasseId}/turnos/${cursoClasseTurnoId}/turmas/${turmaId}`;

  const alunos = turma.alunos ?? [];
  const isEmpty = alunos.length === 0;

  const gerarCertificado = async (e, alunoId) => {
    e.stopPropagation();
    try {
      const response = await fetch(
        gerar({
          instituicao: instituicaoId,
          cursoTutelado: cursoTuteladoId,
          cursoClasse: cursoClasseId,
          cursoClasseTurno: cursoClasseTurnoId,
          turma: turmaId,
          aluno: alunoId,
        }).url,
      );
      const blob = await response.blob();
      const url = window.URL.createObjectURL(blob);
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'certificado.pdf');
      document.body.appendChild(link);
      link.click();
      link.remove();
      window.URL.revokeObjectURL(url);
    } catch (error) {
      console.error('Erro ao gerar certificado:', error);
    }
  };

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Alunos</CardTitle>
        <CardDescription>Alunos inscritos nesta turma</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`#`}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={UsersIcon}
            title="Nenhum aluno inscrito"
            description="Comece adicionando alunos à turma"
            action={{
              label: 'Adicionar Aluno',
              href: `#`,
              variant: 'outline',
            }}
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
                const nome = aluno.inscricao?.candidato?.nome;
                const email = aluno.user?.email;
                const telefone = aluno.user?.telefone;

                return (
                  <TableRow
                    key={aluno.id}
                    className="hover:cursor-pointer"
                    onClick={() => router.visit(show(aluno.id).url)}
                  >
                    <TableCell className="px-4 font-medium">{nome}</TableCell>
                    <TableCell>
                      {aluno.matricula ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>
                    <TableCell>
                      {email ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>
                    <TableCell>
                      {telefone ?? (
                        <Minus size={15} className="text-muted-foreground" />
                      )}
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      <DropdownMenu>
                        <DropdownMenuTrigger asChild>
                          <Button
                            variant="ghost"
                            size="icon"
                            className="size-8"
                          >
                            <MoreHorizontalIcon />
                            <span className="sr-only">Abrir menu</span>
                          </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end">
                          <DropdownMenuItem
                            onClick={(e) => gerarCertificado(e, aluno.id)}
                          >
                            Gerar Certificado
                          </DropdownMenuItem>
                          <DropdownMenuSeparator />
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

      {!isEmpty && pagination && pagination.last_page > 1 && (
        <CardFooter className="justify-between">
          <span className="text-muted-foreground">
            Página {pagination.current_page} de {pagination.last_page}
          </span>

          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious
                  href={pagination.prev_page_url || '#'}
                  disabled={!pagination.prev_page_url}
                />
              </PaginationItem>

              <PaginationItem>
                <PaginationNext
                  href={pagination.next_page_url || '#'}
                  disabled={!pagination.next_page_url}
                />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      )}
    </Card>
  );
}
