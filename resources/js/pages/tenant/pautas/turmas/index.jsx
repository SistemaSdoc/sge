import { Link, router } from '@inertiajs/react';
import {
  Card,
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
import { pauta } from '@/actions/App/Http/Controllers/Tenant/PautaController';

export default function TurmasCurso({
  cursoTutelado,
  turmas = { data: [] },
  anosLectivos = [],
  anoLectivoActual,
}) {
  const listaTurmas = turmas.data ?? turmas;

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
            Pautas — {cursoTutelado?.curso?.nome}
          </h1>
          <p className="text-muted-foreground">
            Selecione uma turma para visualizar a pauta
          </p>
        </div>

        <Select
          value={String(anoLectivoActual ?? '')}
          onValueChange={handleAnoLectivoChange}
        >
          <SelectTrigger id="ano-lectivo" className="w-48">
            <SelectValue placeholder="Selecione o ano lectivo" />
          </SelectTrigger>
          <SelectContent>
            <SelectGroup>
              <SelectLabel>Anos Lectivos</SelectLabel>
              {anosLectivos.map((ano) => (
                <SelectItem key={ano.id} value={String(ano.id)}>
                  {ano.nome}
                </SelectItem>
              ))}
            </SelectGroup>
          </SelectContent>
        </Select>
      </div>

      {listaTurmas.length === 0 ? (
        <EmptyState
          icon={BookOpen}
          title="Nenhuma pauta disponível"
          description="Não existem turmas neste curso para o ano lectivo seleccionado"
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {listaTurmas.map((turma) => (
            <Link key={turma.id} href={pauta({ turma: turma.id })}>
              <Card className="h-full hover:cursor-pointer">
                <CardHeader>
                  <CardTitle>
                    {turma.nome} - {turma.classe} - {turma.turno}
                  </CardTitle>
                  <CardDescription>
                    Clique para consultar a pauta
                  </CardDescription>
                </CardHeader>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  );
}
