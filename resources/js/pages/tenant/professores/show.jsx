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
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Minus } from 'lucide-react';
import { CursosTable } from './components/tabs/cursos.tab';
import { TurmasTab } from './components/tabs/turmas.tab';

export default function Show({ professor, cursos, turmas }) {
  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />

          {/* content*/}
          <div className="relative z-10 space-y-2 p-6 text-white">
            <h1 className="text-2xl font-semibold wrap-break-word md:text-3xl">
              {professor?.user?.nome}
            </h1>

            <p className="text-sm break-all opacity-90">
              {professor?.user?.email}
            </p>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3 lg:grid-cols-4">
          <div>
            <p className="text-sm text-muted-foreground">Nº Bilhete</p>
            <p className="font-medium">
              {professor?.user?.bi || (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Telefone</p>
            <p className="font-medium">
              {professor?.user?.telefone || (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
        </CardContent>

        <div>
          <p className="text-sm text-muted-foreground">Especialidade</p>
          <p className="font-medium">
            {professor?.especialidade || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Nível Académico</p>
          <p className="font-medium">
            {professor?.nivel_academico || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
      </Card>

      <CursosTable cursos={cursos} />
    </div>
  );
}
