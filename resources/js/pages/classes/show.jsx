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

export default function Show({ classe }) {
  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md md:py-40 lg:max-w-2xl">
      <Card>
        <CardHeader>
          <CardTitle>{classe.nome}</CardTitle>
          <CardDescription>{classe.ordem}</CardDescription>
          <CardAction>
            <Button asChild variant="outline">
              <Link href={`/classes/${classe.id}/edit`}>Editar</Link>
            </Button>
          </CardAction>
        </CardHeader>

        <CardFooter></CardFooter>
      </Card>
    </div>
  );
}
