import { Link } from '@inertiajs/react';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { indexTurmas } from '@/actions/App/Http/Controllers/PautaController';

export default function PautasCursos({ cursosTutelados }) {
  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <div>
        <h1 className="text-xl font-bold tracking-tight">Pautas</h1>
        <p className="text-muted-foreground">
          Seleccione um curso para visualizar as pautas
        </p>
      </div>

      {cursosTutelados.length === 0 ? (
        <Card>
          <CardContent className="pt-6">
            <p className="text-center text-muted-foreground">
              Nenhum curso tutelado disponível
            </p>
          </CardContent>
        </Card>
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {cursosTutelados.map((ct) => {
            const podeVer = ct.can?.view_turmas;

            const card = (
              <Card
                aria-disabled={!podeVer}
                className="h-full hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
              >
                <CardHeader>
                  <CardTitle>{ct.curso?.nome}</CardTitle>
                  <CardDescription>{ct.instituicao?.nome}</CardDescription>
                </CardHeader>
              </Card>
            );

            return podeVer ? (
              <Link key={ct.id} href={indexTurmas({ cursoTutelado: ct.id })}>
                {card}
              </Link>
            ) : (
              <div key={ct.id}>{card}</div>
            );
          })}
        </div>
      )}
    </div>
  );
}
