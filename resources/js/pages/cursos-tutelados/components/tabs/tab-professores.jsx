import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';
import { BookOpenIcon } from 'lucide-react';

export function TabProfessores({ instituicaoId }) {
  const isEmpty = true;

  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Professores</CardTitle>
        <CardDescription>Professores associados a este curso</CardDescription>
      </CardHeader>

      <CardContent className="p-0!">
        <EmptyState
          variant="table"
          icon={BookOpenIcon}
          title="Nenhum professor associado"
          description="Comece adicionando professores ao curso"
        />
      </CardContent>

      <CardFooter className="justify-between">
        <span className="text-muted-foreground">Página 1 de 1</span>
      </CardFooter>
    </Card>
  );
}
