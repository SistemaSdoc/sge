import { Card, CardContent } from '@/components/ui/card';
import { Minus } from 'lucide-react';

export function AlunoRelated({ aluno }) {
  return (
    <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
      <Card>
        <CardContent className="p-4">
          <p className="text-sm text-muted-foreground">Curso</p>
          <p className="font-medium">{aluno.curso ?? '—'}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent className="p-4">
          <p className="text-sm text-muted-foreground">Turno</p>
          <p className="font-medium">{aluno.turno ?? '—'}</p>
        </CardContent>
      </Card>
      <Card>
        <CardContent className="p-4">
          <p className="text-sm text-muted-foreground">Turma actual</p>
          <p className="font-medium">
            {aluno.turma?.nome ? (
              `${aluno.turma.nome} — ${aluno.turma.classe}`
            ) : (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </CardContent>
      </Card>
    </div>
  );
}
