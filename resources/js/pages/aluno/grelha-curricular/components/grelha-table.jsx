import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { BookOpenIcon, Filter } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

/**
 * Componente de tabela para exibição de notas do aluno.
 *
 * Estrutura:
 * - Coluna: Disciplina
 * - Colunas: Prova 1, Prova 2, Prova 3, Média Trimestral, Classificação
 *
 * @component
 * @param {Array} data - Array de notas com provas por trimestre
 * @param {number} trimestre - Trimestre selecionado (1, 2 ou 3)
 * @returns {JSX.Element}
 */
export function GrelhaTable({ data = [] }) {
  const isEmpty = !data || data.length === 0;

  return (
    <Card className="w-full gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Lista de disciplinas</CardTitle>

        <CardDescription>
          Visualize todas as disciplinas da sua turma e respetivos professores
        </CardDescription>
      </CardHeader>

      <CardContent className="gap-0 p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={BookOpenIcon}
            title="Sem disciplinas atribuídas"
            description="Nenhuma grelha curricular disponível no momento"
          />
        ) : (
          <div className="w-full overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Sigla</TableHead>
                  <TableHead className="px-4">Disciplina</TableHead>
                  <TableHead className="min-w px-4">Professor</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {data.map((item) => {
                  return (
                    <TableRow key={item.sigla}>
                      <TableCell className="px-4 font-medium">
                        {item.sigla}
                      </TableCell>

                      <TableCell className="px-4 font-medium">
                        {item.disciplina}
                      </TableCell>

                      <TableCell className="px-4">{item.professor}</TableCell>
                    </TableRow>
                  );
                })}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
