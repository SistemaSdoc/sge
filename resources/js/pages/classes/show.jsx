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
import { edit } from '@/actions/App/Http/Controllers/ClasseController';

export default function Show({ classe, can = {} }) {
  const canEdit = Boolean(can?.edit_classe || can?.update);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md md:py-40 lg:max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>{classe.nome}</CardTitle>
          <CardDescription>{classe.ordem}</CardDescription>
          {canEdit && (
            <CardAction>
              <Button asChild variant="outline">
                <Link href={edit(classe.id).url}>Editar</Link>
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardFooter></CardFooter>
      </Card>
    </div>
  );
}
