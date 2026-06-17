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
          {cursosTutelados.map((ct) => (
            <Link key={ct.id} href={indexTurmas({ cursoTutelado: ct.id })}>
              <Card className="h-full cursor-pointer transition-shadow hover:shadow-lg">
                <CardHeader>
                  <CardTitle>{ct.curso?.nome}</CardTitle>
                  <CardDescription>{ct.instituicao?.nome}</CardDescription>
                </CardHeader>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
