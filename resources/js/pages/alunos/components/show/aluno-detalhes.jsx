import { Card, CardContent } from '@/components/ui/card';
import { Minus } from 'lucide-react';

export function AlunoDetails({ aluno }) {
  return (
    <Card>
      <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
        <div>
          <p className="text-sm text-muted-foreground">Nº Bilhete</p>
          <p className="font-medium">
            {aluno.bi || <Minus size={15} className="text-muted-foreground" />}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Telefone</p>
          <p className="font-medium">
            {aluno.telefone || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Email</p>
          <p className="font-medium">
            {aluno.email || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Matrícula</p>
          <p className="font-medium">
            {aluno.matricula || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
