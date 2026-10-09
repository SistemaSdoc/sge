import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import { LayersIcon } from 'lucide-react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { EmptyState } from '@/components/empty-state';
import { edit } from '@/actions/App/Http/Controllers/Tenant/PeriodoLancamentoNotasController';

export default function SolicitacoesIndex() {
  const { solicitacoes, instituicao } = usePage().props;
  const [prazos, setPrazos] = useState({});

  const decidir = (id, decisao) => {
    router.post(
      `/dashboard/pautas/solicitacoes/${id}/decidir`,
      {
        decisao,
        prazo_edicao_ate: prazos[id] ?? null,
      },
      { preserveScroll: true },
    );
  };

  return (
    <div className="space-y-4 p-4 sm:p-6">
      <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <h1 className="text-xl font-semibold">
          Solicitações de Lançamento de Notas
        </h1>

        <Button asChild className="w-full shrink-0 sm:w-auto">
          <Link href={edit({ instituicao: instituicao.id }).url}>
            Definir prazos de lançamentos
          </Link>
        </Button>
      </div>

      {solicitacoes.length === 0 && (
        <Card className="border-0">
          <CardContent className="p-0!">
            <EmptyState
              variant="table"
              icon={LayersIcon}
              title="Nenhuma solicitação pendente"
              description="Não existem pedidos de lançamento de notas para tratar neste momento."
            />
          </CardContent>
        </Card>
      )}

      {solicitacoes.map((s) => (
        <Card key={s.id}>
          <CardHeader className="pb-2">
            <div className="flex flex-col items-start gap-2 sm:flex-row sm:items-center sm:justify-between sm:gap-3">
              <CardTitle className="wrap-break-words text-base">
                {s.disciplina} — {s.turma}
              </CardTitle>
              <Badge className="shrink-0 bg-yellow-50 text-yellow-700">
                {s.tipo === 'reabertura_edicao'
                  ? 'Reabertura de edição'
                  : 'Extensão de prazo'}
              </Badge>
            </div>
          </CardHeader>

          <CardContent className="space-y-3">
            <p className="text-sm">
              <span className="text-muted-foreground">Professor:</span>{' '}
              {s.professor}
            </p>
            <p className="text-sm">
              <span className="text-muted-foreground">Período:</span>{' '}
              {s.periodo}º Trimestre
            </p>
            <p className="wrap-break-words text-sm">
              <span className="text-muted-foreground">Motivo:</span> {s.motivo}
            </p>
            <p className="text-xs text-muted-foreground">{s.created_at}</p>

            {s.tipo === 'extensao_prazo' && (
              <a
                href={s.link_prazos}
                className="text-xs text-blue-600 underline"
              >
                Ver/ajustar prazos de lançamento
              </a>
            )}

            <div className="space-y-1">
              <p className="text-xs text-muted-foreground">
                Prazo de edição (obrigatório para aprovar)
              </p>
              <Input
                type="datetime-local"
                value={prazos[s.id] ?? ''}
                onChange={(e) =>
                  setPrazos((prev) => ({ ...prev, [s.id]: e.target.value }))
                }
              />
            </div>

            <div className="flex flex-col gap-2 pt-2 sm:flex-row sm:flex-wrap">
              <Button
                size="sm"
                disabled={!prazos[s.id]}
                onClick={() => decidir(s.id, 'aprovada')}
              >
                Aprovar
              </Button>
              <Button
                size="sm"
                variant="destructive"
                onClick={() => decidir(s.id, 'rejeitada')}
              >
                Rejeitar
              </Button>
            </div>
          </CardContent>
        </Card>
      ))}
    </div>
  );
}