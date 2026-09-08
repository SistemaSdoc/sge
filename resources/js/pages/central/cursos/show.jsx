import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/actions/App/Http/Controllers/Central/CursoController';

export default function Show({ curso }) {
  return (
    <>
      <Head title={curso.nome} />
      <div className="mx-auto w-full max-w-3xl p-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>{curso.nome}</CardTitle>
            <Button asChild size="sm">
              <Link href={edit(curso.id).url}>Editar</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3 pt-6">
            <p>{curso.descricao || 'Sem descrição.'}</p>
            <p>Duração: {curso.duracao_anos} anos</p>
            <p>Status: {curso.status === 1 ? 'Activo' : 'Inactivo'}</p>
          </CardContent>
        </Card>
      </div>
    </>
  );
}
