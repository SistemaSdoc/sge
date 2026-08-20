import { Badge } from '@/components/ui/badge';
import { TabsList, TabsTrigger } from '@/components/ui/tabs';

export function TurmaTabsList({ classe, totalRecurso }) {
  return (
    <TabsList>
      <TabsTrigger value="alunos" className="hover:cursor-pointer">
        Alunos da turma
      </TabsTrigger>
      <TabsTrigger value="disciplinas" className="hover:cursor-pointer">
        Disciplinas da turma
      </TabsTrigger>

      {classe?.nome === '13ª' && (
        <TabsTrigger value="grupos-pap" className="hover:cursor-pointer">
          Grupos para PAP
        </TabsTrigger>
      )}

      {totalRecurso > 0 && (
        <TabsTrigger
          value="recurso"
          className="text-blue-600 hover:cursor-pointer"
        >
          Recurso
          <Badge className="ml-2 bg-blue-50 text-xs text-blue-600">
            {totalRecurso}
          </Badge>
        </TabsTrigger>
      )}
    </TabsList>
  );
}
