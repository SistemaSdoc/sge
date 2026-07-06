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
import { pauta } from '@/actions/App/Http/Controllers/PautaController';

export default function Index({ cursoTutelado, turmas = [] }) {
  const isEmpty = !turmas || turmas.length === 0;

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <div>
        {/*{cursoTutelado && (
          <Link href={`/instituicoes/${cursoTutelado.id}`}>
            <Button variant="ghost" size="sm" className="mb-2">
              <ChevronLeft className="mr-1 size-4" />
              Voltar
            </Button>
          </Link>
        )}*/}
        <h1 className="text-xl font-bold">
          {cursoTutelado ? `Pautas — ${cursoTutelado.curso?.nome}` : 'Pautas'}
        </h1>

        <p className="text-muted-foreground">
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
                  cursoTutelado: cursoTutelado.id,
                  turma: turma.id,
                }).url
              }
            >
              <Card className="h-full cursor-pointer">
                <CardHeader>
                  <CardTitle>
                    {turma.nome} - {turma.classe} - {turma.turno}
                  </CardTitle>
                  <CardDescription>Clique para ver a pauta</CardDescription>
                </CardHeader>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
