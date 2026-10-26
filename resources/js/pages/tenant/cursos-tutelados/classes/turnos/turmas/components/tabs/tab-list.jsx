import { Badge } from '@/components/ui/badge';
import { TabsList, TabsTrigger } from '@/components/ui/tabs';

export function TurmaTabsList({ classe, totalRecurso }) {
  return (
    <TabsList className="max-md:grid max-md:h-auto! max-md:w-full max-md:grid-cols-2 max-md:gap-1">
      <TabsTrigger
        value="alunos"
        className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
      >
        Alunos da turma
      </TabsTrigger>
      <TabsTrigger
        value="disciplinas"
        className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
      >
        Disciplinas da turma
      </TabsTrigger>

      {classe?.nome === '13ª' && (
        <TabsTrigger
          value="grupos-pap"
          className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
        >
          Grupos para PAP
        </TabsTrigger>
      )}

      {totalRecurso > 0 && (
        <TabsTrigger
          value="recurso"
          className="text-blue-600 hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
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
