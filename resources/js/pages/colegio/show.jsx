import { router } from '@inertiajs/react';

import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

import { show } from '@/actions/App/Http/Controllers/Colegios/CursoTuteladoController';
import { edit as editarPrazosLancamentoNotas } from '@/actions/App/Http/Controllers/PeriodoLancamentoNotasController';
import TablePagination from '@/components/table-pagination';

export default function Show({
  instituicao,
  colegio,
  cursos,
  can = {},
  pagination = {},
  onPageChange,
}) {
  console.log('Props recebidas:', { instituicao, colegio, cursos, pagination });
  const lista = cursos?.data ?? [];
  console.log('Props recebidas:', { instituicao, colegio, cursos, pagination });

  const handlePageChange = (page) => {
    router.get(
      route('colegios.show', {
        instituicao: instituicao.id,
        colegio: colegio.id,
        page,
      }),
    );
  };

  const handleBack = () => {
    router.get(
      route('colegios.index', {
        instituicao: instituicao.id,
      }),
    );
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-4 p-6">
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div>
            <CardTitle>{colegio.nome}</CardTitle>
            <CardDescription>Cursos tutelados do colégio</CardDescription>
          </div>
        </CardHeader>

        <CardContent className="p-0!">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome do Curso</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {lista.map((curso) => (
                <TableRow
                  key={curso.id}
                  className="cursor-pointer hover:bg-muted/50"
                  onClick={() =>
                    router.visit(
                      show({
                        instituicao: instituicao.id,
                        colegio: colegio.id,
                        cursoTutelado: curso.curso_tutelado_id,
                      }).url,
                    )
                  }
                >
                  <TableCell className="px-4 font-medium">
                    {curso.nome}
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>

        <TablePagination pagination={cursos} onPageChange={handlePageChange} />
      </Card>
    </div>
  );
}
