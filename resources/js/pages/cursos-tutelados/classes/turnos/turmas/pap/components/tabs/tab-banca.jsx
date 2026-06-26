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
import { MoreHorizontalIcon, Minus, Users2Icon } from 'lucide-react';
import { Link, router } from '@inertiajs/react';
import { EmptyState } from '@/components/empty-state';
import { show as showProfessor } from '@/actions/App/Http/Controllers/ProfessorController';
import { create as adicionarJurado, edit } from '@/actions/App/Http/Controllers/BancaJuriPapController';
import TablePagination from '@/components/table-pagination';

export function TabBanca({
  params,
  grupoPap,
  pagination,
  onPageChange,
  removerJuradoFn,
}) {
  const jurados = pagination?.data ?? [];
  const isEmpty = jurados.length === 0;
  //console.log('pagination:', pagination); 
  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Integrantes da banca</CardTitle>
        <CardDescription>Professores avaliadores e funções</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={adicionarJurado.url(params)}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users2Icon}
            title="Nenhum membro da banca"
            description="Comece adicionando os jurados para a defesa do grupo PAP"
            action={{
              label: 'Adicionar juri',
              href: adicionarJurado.url(params),
              variant: 'outline',
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Função</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {jurados.map((j) => (
                <TableRow
                  key={j.id}
                  className="hover:cursor-pointer"
                  onClick={() =>
                    router.visit(showProfessor.url({ professor: j.professor_id }))
                  }
                >
                  <TableCell className="px-4 font-medium">{j.nome}</TableCell>

                  <TableCell>
                    {j.email ?? (
                      <Minus size={15} className="text-muted-foreground" />
                    )}
                  </TableCell>

                  <TableCell className="capitalize">{j.funcao}</TableCell>

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
                          onClick={() => router.visit(edit.url({
                            ...params,
                            bancaJuriPap: j.id, // id do jurado da linha
                          }))}
                        >
                          Editar
                        </DropdownMenuItem>

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={() => removerJuradoFn(j.id)}
                        >
                          Remover da banca
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
      <TablePagination pagination={pagination.meta} onPageChange={onPageChange} />
    </Card>
  );
}
