import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Link } from '@inertiajs/react';
import { edit } from '@/actions/App/Http/Controllers/CursosController';

export default function Show({ curso }) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md md:py-40 lg:max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>{curso.nome}</CardTitle>
          <CardDescription>Duração: {curso.duracao_anos} anos</CardDescription>
          <CardAction>
            <Button asChild variant="outline">
              <Link href={edit(curso.id).url}>Editar</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent>
          <p>{curso.descricao}</p>
        </CardContent>

        <CardFooter></CardFooter>
      </Card>
    </div>
  );
}
