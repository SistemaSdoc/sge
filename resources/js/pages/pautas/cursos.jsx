import { Link } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';


export default function PautasCursos({ cursosTutelados }) {
  return (
      <div className="p-6 space-y-6">
        <div>
          <h1 className="text-3xl font-bold tracking-tight">Pautas</h1>
          <p className="text-muted-foreground mt-2">
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
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {cursosTutelados.map((ct) => (
              <Link
                key={ct.id}
                href={`/instituicoes/${ct.instituicao?.id}/cursos-tutelados/${ct.id}/pautas`}
              >
                <Card className="cursor-pointer hover:shadow-lg transition-shadow h-full">
                  <CardHeader>
                    <CardTitle className="text-lg">
                      {ct.curso?.nome}
                    </CardTitle>
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
