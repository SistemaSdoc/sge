import { router } from '@inertiajs/react';
import { Download, Trophy } from 'lucide-react';
import { index as quadroHonraIndex } from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
import { EmptyState } from '@/components/empty-state';
import TablePagination from '@/components/table-pagination';
import { Badge } from '@/components/ui/badge';
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
import type {
  FiltroQuadrosHonra,
  Paginacao,
  QuadroHonraResumo,
} from '@/types/quadro-honra';

type ListaQuadrosHonraProps = {
  quadros: Paginacao<QuadroHonraResumo>;
  filtros: FiltroQuadrosHonra;
};

/** Apresenta os quadros de honra registados, o estado vazio e as ligações para os respectivos documentos PDF. */
export function ListaQuadrosHonra({
  quadros,
  filtros,
}: ListaQuadrosHonraProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>Quadros de honra gerados</CardTitle>
        <CardDescription>
          Total de quadros registados: {quadros.total}. Ordenação do mais
          recente para o mais antigo.
        </CardDescription>
      </CardHeader>
      <CardContent>
        {quadros.data.length === 0 ? (
          <EmptyState
            icon={Trophy}
            title="Não existem quadros de honra gerados"
            description="Os quadros de honra gerados serão apresentados nesta lista."
            variant="table"
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Ano lectivo</TableHead>
                <TableHead>Tipo de quadro</TableHead>
                <TableHead>Curso</TableHead>
                <TableHead>Média mínima</TableHead>
                <TableHead>Nº de alunos</TableHead>
                <TableHead>Data de geração</TableHead>
                <TableHead className="text-right">Documento</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {quadros.data.map((quadro) => (
                <TableRow key={quadro.id}>
                  <TableCell>{quadro.ano_lectivo ?? '—'}</TableCell>
                  <TableCell>
                    <Badge
                      variant={
                        quadro.tipo === 'geral' ? 'default' : 'secondary'
                      }
                    >
                      {quadro.tipo === 'geral' ? 'Geral' : 'Por curso'}
                    </Badge>
                  </TableCell>
                  <TableCell>{quadro.curso ?? 'Todos os cursos'}</TableCell>
                  <TableCell>
                    {Number(quadro.media_minima).toLocaleString('pt-AO')}{' '}
                    valores
                  </TableCell>
                  <TableCell>{quadro.quantidade_alunos}</TableCell>
                  <TableCell>
                    {quadro.gerado_em
                      ? new Date(quadro.gerado_em).toLocaleDateString('pt-AO')
                      : '—'}
                  </TableCell>
                  <TableCell className="text-right">
                    <Button asChild variant="outline" size="sm">
                      <a href={quadro.pdf_url} target="_blank" rel="noreferrer">
                        <Download className="size-4" />
                        PDF
                      </a>
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}

        {quadros.last_page > 1 && (
          <TablePagination
            pagination={quadros}
            onPageChange={(page: number) =>
              router.get(
                quadroHonraIndex().url,
                {
                  ...filtros,
                  page,
                },
                {
                  preserveScroll: true,
                  preserveState: true,
                },
              )
            }
          />
        )}
      </CardContent>
    </Card>
  );
}
