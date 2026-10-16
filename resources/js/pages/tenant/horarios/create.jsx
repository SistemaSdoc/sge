import { useState } from 'react';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ArrowUpLeft } from 'lucide-react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { store } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoDisciplinaHorarioController';
import { show } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';
import { HorariosForm } from '@/pages/tenant/cursos-tutelados/classes/turnos/turmas/components/horarios/horarios-form';

export default function HorariosCreate({
  params,
  turma,
  disciplina,
  horarios = [],
  anoLectivoId,
  readOnly = false,
}) {
  const { errors } = usePage().props;
  const [processing, setProcessing] = useState(false);

  const voltarUrl = show(
    {
      instituicao: params.instituicao,
      cursoTutelado: params.cursoTutelado,
      cursoClasse: params.cursoClasse,
      cursoClasseTurno: params.cursoClasseTurno,
      turma: params.turma,
    },
    { query: { ano_lectivo_id: anoLectivoId } },
  ).url;

  const handleSubmit = (payload) => {
    router.post(
      store(params).url,
      { horarios: payload, ano_lectivo_id: anoLectivoId },
      {
        preserveScroll: true,
        onStart: () => setProcessing(true),
        onFinish: () => setProcessing(false),
      },
    );
  };

  const primeiroErro = Object.values(errors ?? {})[0];

  const titulo = readOnly ? 'Horários' : 'Definir horários';

  return (
    <div className="mx-auto w-full max-w-2xl p-4 md:p-6">
      <Head title={titulo} />

      <Card>
        <CardHeader className="border-b">
          <CardTitle>{titulo}</CardTitle>
          <CardDescription>
            Disciplina{' '}
            <span className="font-medium text-foreground">
              {disciplina.nome}
            </span>
            {' · '}Turma{' '}
            <span className="font-medium text-foreground">{turma.nome}</span>
          </CardDescription>
        </CardHeader>

        <CardContent className="space-y-4">
          <HorariosForm
            onSubmit={handleSubmit}
            isLoading={processing}
            defaultValues={horarios}
            readOnly={readOnly}
          />

          {primeiroErro && (
            <p className="text-sm text-destructive">{primeiroErro}</p>
          )}

          <Button asChild variant="outline" className="w-full">
            <Link href={voltarUrl}>
              <ArrowUpLeft aria-hidden="true" />
              Voltar à turma
            </Link>
          </Button>
        </CardContent>
      </Card>
    </div>
  );
}
