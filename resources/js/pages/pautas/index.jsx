import { Link } from '@inertiajs/react';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { BookOpen, ChevronLeft } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { pauta } from '@/actions/App/Http/Controllers/NotaController';

export default function Index({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turmas = [],
}) {
  const isEmpty = !turmas || turmas.length === 0;
  console.log({
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turmas,
  });

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <div>
        {cursoTutelado && (
          <Link href={`/instituicoes/${cursoTutelado.id}`}>
            <Button variant="ghost" size="sm" className="mb-2">
              <ChevronLeft className="mr-1 size-4" />
              Voltar
            </Button>
          </Link>
        )}
        <h1 className="text-2xl font-bold">
          {cursoTutelado ? `Pautas — ${cursoTutelado.curso?.nome}` : 'Pautas'}
        </h1>
        <p className="mt-1 text-sm text-muted-foreground">
          Selecione uma turma para visualizar a pauta
        </p>
      </div>

      {isEmpty ? (
        <EmptyState
          icon={BookOpen}
          title="Nenhuma pauta disponível"
          description="Não tem acesso a nenhuma turma com pautas"
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {turmas.map((turma) => (
            <Link
              key={turma.id}
              href={
                pauta({
                  instituicao: instituicao.id,
                  cursoTutelado: cursoTutelado.id,
                  cursoClasse: turma.cursoClasse.id,
                  cursoClasseTurno: turma.cursoClasseTurno.id,
                  turma: turma.id,
                }).url
              }
            >
              <Card className="h-full cursor-pointer transition-shadow hover:shadow-lg">
                <CardHeader>
                  <CardTitle className="text-lg">
                    {turma.nome} - {turma.classe} -{turma.turno}
                  </CardTitle>
                  <CardDescription>
                    {turma.curso?.nome || 'Sem curso'}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="flex flex-wrap gap-1">
                      {turma.instituicao?.nome && (
                        <Badge variant="secondary">
                          {turma.instituicao.nome}
                        </Badge>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Clique para ver a pauta
                    </p>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
