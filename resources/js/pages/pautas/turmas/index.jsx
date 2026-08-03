import { Link, router } from '@inertiajs/react';
import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { BookOpen } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { pauta } from '@/actions/App/Http/Controllers/PautaController';

export default function Index({
  cursoTutelado,
  turmas = [],
  anosLectivos = [],
  anoLectivoActual,
}) {
  const isEmpty = !turmas || turmas.length === 0;

  const handleAnoLectivoChange = (value) => {
    router.visit(window.location.pathname, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
      <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
        <div>
          <h1 className="text-xl font-bold">
            {cursoTutelado ? `Pautas — ${cursoTutelado.curso?.nome}` : 'Pautas'}
          </h1>
          <p className="text-muted-foreground">
            Selecione uma turma para visualizar a pauta
          </p>
        </div>

        <Select
          value={anoLectivoActual ?? ''}
          onValueChange={handleAnoLectivoChange}
        >
          <SelectTrigger id="ano-lectivo" className="w-48">
            <SelectValue placeholder="Selecione o ano lectivo" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectLabel>Anos Lectivos</SelectLabel>
              {anosLectivos?.map((ano) => (
                <SelectItem key={ano.id} value={ano.id}>
                  {ano.nome}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
      </div>

      {isEmpty ? (
        <EmptyState
          icon={BookOpen}
          title="Nenhuma pauta disponível"
          description="Não tem acesso a nenhuma turma com pautas"
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {turmas.map((turma) => {
            const podeVer = turma.can?.view_pauta;

            const card = (
              <Card
                aria-disabled={!podeVer}
                className="h-full hover:cursor-pointer aria-disabled:cursor-not-allowed aria-disabled:opacity-50"
              >
                <CardHeader>
                  <CardTitle>
                    {turma.nome} - {turma.classe} - {turma.turno}
                  </CardTitle>
                  <CardDescription>
                    {podeVer
                      ? 'Clique para ver a pauta'
                      : 'Sem acesso a esta turma'}
                  </CardDescription>
                </CardHeader>
              </Card>
            );

            return podeVer ? (
              <Link
                key={turma.id}
                href={
                  pauta({ cursoTutelado: cursoTutelado.id, turma: turma.id })
                    .url
                }
              >
                {card}
              </Link>
            ) : (
              <div key={turma.id}>{card}</div>
            );
          })}
        </div>
      )}
    </div>
  );
}
