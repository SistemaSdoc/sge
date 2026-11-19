import { router } from '@inertiajs/react';
import { Trophy } from 'lucide-react';
import { index as quadroHonraIndex } from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
import { EmptyState } from '@/components/empty-state';
import TablePagination from '@/components/table-pagination';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import type {
  FiltroQuadrosHonra,
  Paginacao,
  QuadroHonraResumo,
} from '@/types/quadro-honra';

type ListaQuadrosHonraProps = {
  quadros: Paginacao<QuadroHonraResumo>;
  filtros: FiltroQuadrosHonra;
};

function iniciais(nome: string): string {
  return nome
    .split(' ')
    .filter(Boolean)
    .slice(0, 2)
    .map((parte) => parte[0])
    .join('')
    .toUpperCase();
}

/** Apresenta os estudantes do quadro em cartões de perfil. */
export function ListaQuadrosHonra({
  quadros,
  filtros,
}: ListaQuadrosHonraProps) {
  return (
    <section className="flex flex-col gap-6">
      {quadros.data.length === 0 ? (
        <Card>
          <CardContent className="pt-6">
            <EmptyState
              icon={Trophy}
              title="Ainda não há alunos no Quadro de Honra"
            />
          </CardContent>
        </Card>
      ) : (
        quadros.data.map((quadro) => (
          <Card key={quadro.id}>
            <CardHeader>
              <div className="flex flex-wrap items-start justify-between gap-3">
                <div className="space-y-1">
                  <CardTitle>Quadro de Honra</CardTitle>
                  <CardDescription>
                    Ano lectivo {quadro.ano_lectivo ?? '-'}
                    {' · '}
                    {quadro.periodo}.º trimestre
                  </CardDescription>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                {quadro.alunos.map((aluno) => (
                  <article
                    key={aluno.id}
                    className="flex flex-col items-center gap-3 rounded-lg border bg-card p-5 text-center"
                  >
                    <Avatar className="size-20">
                      <AvatarImage
                        src={aluno.avatar_url ?? undefined}
                        alt={aluno.nome}
                      />
                      <AvatarFallback className="text-lg">
                        {iniciais(aluno.nome)}
                      </AvatarFallback>
                    </Avatar>
                    <div className="space-y-1">
                      <p className="font-semibold">
                        <span className="text-muted-foreground">
                          {aluno.posicao}.{' '}
                        </span>
                        {aluno.nome}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        Curso: {aluno.curso}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        Classe: {aluno.classe}
                      </p>
                      <p className="text-sm text-muted-foreground">
                        Turma: {aluno.turma}
                      </p>
                    </div>
                    <Badge variant="secondary">
                      Média:{' '}
                      {aluno.media.toLocaleString('pt-AO', {
                        minimumFractionDigits: 1,
                        maximumFractionDigits: 1,
                      })}
                    </Badge>
                  </article>
                ))}
              </div>
            </CardContent>
          </Card>
        ))
      )}

      {quadros.last_page > 1 && (
        <TablePagination
          pagination={quadros}
          onPageChange={(page: number) =>
            router.get(
              quadroHonraIndex().url,
              {
                ...filtros,
                page,
              },
              {
                preserveScroll: true,
                preserveState: true,
              },
            )
          }
        />
      )}
    </section>
  );
}
