import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

export function AlunoActivity({ aluno }) {
  return (
    <>
      <Card>
        <CardHeader>
          <CardTitle className="text-base">Histórico académico</CardTitle>
        </CardHeader>

        <CardContent>
          {aluno.historico?.length ? (
            <ol className="relative space-y-6 border-l pl-5">
              {aluno.historico.map((item) => (
                <li key={item.id} className="relative">
                  <span className="absolute top-1 -left-6.75 size-2.5 rounded-full border-2 border-primary bg-background" />
                  <div className="flex items-center justify-between gap-2">
                    <p className="text-sm font-medium">{item.classe}</p>
                    <Badge variant="outline">{item.ano_lectivo}</Badge>
                  </div>
                  <p className="text-xs text-muted-foreground">
                    {item.resultado ?? item.status}
                  </p>
                </li>
              ))}
            </ol>
          ) : (
            <p className="text-sm text-muted-foreground">
              Ainda sem registos de histórico académico.
            </p>
          )}
        </CardContent>
      </Card>
    </>
  );
}
