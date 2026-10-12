import { Head, Link } from '@inertiajs/react';
import { useMemo } from 'react';
import {
  ArrowLeft,
  Plus,
  BookOpen,
  GraduationCap,
  Clock,
  Eye,
  Calendar,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Empty,
  EmptyDescription,
  EmptyHeader,
  EmptyMedia,
  EmptyTitle,
} from '@/components/ui/empty';
import { PeriodoBadge } from '@/components/periodo-badge';
import { create as planificacoesCreate } from '@/actions/App/Http/Controllers/Tenant/PlanificacaoController';

export default function Disciplina({
  disciplina,
  planificacoes = [],
  can = {},
}) {
  // Agrupa por classe
  const grupos = useMemo(() => {
    const mapa = new Map();

    planificacoes.forEach((p) => {
      const key = p.classe?.id ?? 'sem-classe';

      if (!mapa.has(key)) {
        mapa.set(key, {
          classe: p.classe ?? { id: 'sem-classe', nome: 'Sem classe' },
          items: [],
        });
      }

      mapa.get(key).items.push(p);
    });

    const lista = Array.from(mapa.values());
    lista.forEach((g) =>
      g.items.sort((a, b) => (a.periodo ?? '').localeCompare(b.periodo ?? ''))
    );

    return lista.sort((a, b) =>
      (a.classe.nome ?? '').localeCompare(b.classe.nome ?? '')
    );
  }, [planificacoes]);

  return (
    <>
      <Head title={`Planificações — ${disciplina.nome}`} />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-4 sm:p-6">
        {/* Voltar */}
        <Button variant="ghost" size="sm" asChild>
          <Link href="/dashboard/planificacoes">
            <ArrowLeft className="mr-1 size-4" />
            Voltar às planificações
          </Link>
        </Button>

        {/* Cabeçalho da disciplina */}
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
          <div className="flex items-center gap-3">
            <div className="flex size-12 items-center justify-center rounded-lg bg-primary/10">
              <BookOpen className="size-6 text-primary" />
            </div>
            <div>
              <h1 className="text-2xl font-bold tracking-tight">
                {disciplina.nome}
              </h1>
              {disciplina.sigla && (
                <p className="text-sm text-muted-foreground">
                  {disciplina.sigla}
                </p>
              )}
            </div>
          </div>

          {can.create && (
            <Button asChild>
              <Link href={planificacoesCreate().url}>
                <Plus className="mr-1.5 size-4" />
                Nova planificação
              </Link>
            </Button>
          )}
        </div>

        {/* Lista por classe */}
        {planificacoes.length === 0 ? (
          <Empty>
            <EmptyHeader>
              <EmptyMedia variant="icon"><BookOpen /></EmptyMedia>
              <EmptyTitle>Sem planificações</EmptyTitle>
              <EmptyDescription>
                Esta disciplina ainda não tem planificações publicadas.
              </EmptyDescription>
            </EmptyHeader>
          </Empty>
        ) : (
          <div className="space-y-6">
            {grupos.map(({ classe, items }) => (
              <section key={classe.id} className="space-y-3">
                {/* Header da classe */}
                <div className="flex items-center gap-2">
                  <GraduationCap className="size-5 text-primary" />
                  <h2 className="text-lg font-semibold">{classe.nome}</h2>
                  <Badge variant="secondary">{items.length}</Badge>
                </div>

                {/* Grid de planificações */}
                <div className="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                  {items.map((p) => (
                    <Card
                      key={p.id}
                      className="flex flex-col transition-shadow hover:shadow-md"
                    >
                      <CardHeader className="pb-3">
                        <div className="flex items-start justify-between gap-2">
                          <PeriodoBadge periodo={p.periodo} className="text-[10px]" />
                          <Badge variant="outline" className="text-[10px]">
                            v{p.versao}
                          </Badge>
                        </div>
                        <CardTitle className="mt-2 text-base leading-tight">
                          {p.titulo}
                        </CardTitle>
                        <CardDescription className="flex items-center gap-1 text-xs">
                          <Clock className="size-3" />
                          {p.atualizada_em}
                        </CardDescription>
                      </CardHeader>

                      <CardContent className="flex-1 pb-3">
                        {p.ano_letivo && (
                          <p className="flex items-center gap-1 text-xs text-muted-foreground">
                            <Calendar className="size-3" />
                            {p.ano_letivo}
                          </p>
                        )}
                      </CardContent>

                      <div className="border-t p-3">
                        <Button variant="outline" asChild className="w-full">
                          <Link href={`/dashboard/planificacoes/${p.id}`}>
                            <Eye className="mr-1.5 size-4" />
                            Ver detalhes
                          </Link>
                        </Button>
                      </div>
                    </Card>
                  ))}
                </div>
              </section>
            ))}
          </div>
        )}
      </div>
    </>
  );
}