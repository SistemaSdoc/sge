import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { index } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';

export default function PautasCursos({ cursosTutelados }) {
  return (
    <div className="space-y-6 p-6">
      <div>
        <h1 className="text-3xl font-bold tracking-tight">Pautas</h1>
        <p className="mt-2 text-muted-foreground">
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
            <Link
              key={ct.id}
              href={
                index[
                  '/dashboard/instituicoes/{instituicao}/cursos-tutelados/{cursoTutelado}/turmas'
                ]({
                  instituicao: ct.instituicao.id,
                  cursoTutelado: ct.curso.id,
                }).url
              }
            >
              <Card className="h-full cursor-pointer transition-shadow hover:shadow-lg">
                <CardHeader>
                  <CardTitle className="text-lg">{ct.curso?.nome}</CardTitle>
                </CardHeader>
                <CardContent>
                  <p className="text-sm text-muted-foreground">
                    {ct.instituicao?.nome}
                  </p>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
