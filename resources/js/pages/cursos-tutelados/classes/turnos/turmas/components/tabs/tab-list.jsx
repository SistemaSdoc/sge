import { Badge } from '@/components/ui/badge';
import { TabsList, TabsTrigger } from '@/components/ui/tabs';

export function TurmaTabsList({ classe, totalRecurso }) {
  return (
    <TabsList variant={'default'}>
      <TabsTrigger value="alunos">Alunos da turma</TabsTrigger>
      <TabsTrigger value="disciplinas">Disciplinas da turma</TabsTrigger>

      {classe?.nome === '13ª' && (
        <TabsTrigger value="grupos-pap">Grupos para PAP</TabsTrigger>
      )}

      {totalRecurso > 0 && (
        <TabsTrigger value="recurso" className="text-blue-600">
          Recurso
          <Badge className="ml-2 bg-blue-50 text-xs text-blue-600">
            {totalRecurso}
          </Badge>
        </TabsTrigger>
      )}
    </TabsList>
  );
}
