import { Head, Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { edit } from '@/actions/App/Http/Controllers/Central/DisciplinaController';

export default function Show({ disciplina }) {
  return (
    <>
      <Head title={disciplina.nome} />
      <div className="mx-auto w-full max-w-3xl p-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between border-b">
            <CardTitle>{disciplina.nome}</CardTitle>
            <Button asChild size="sm">
              <Link href={edit(disciplina.id).url}>Editar</Link>
            </Button>
          </CardHeader>
          <CardContent className="space-y-3 pt-6">
            <p>Sigla: {disciplina.sigla || 'Não definida'}</p>
            <p>Componente: {disciplina.componente || 'Não definido'}</p>
            <p>Carga horária: {disciplina.carga_horaria} horas</p>
            <p>Status: {disciplina.status === 1 ? 'Activa' : 'Inactiva'}</p>
          </CardContent>
        </Card>
      </div>
    </>
  );
}
