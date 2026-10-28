import { Link } from '@inertiajs/react';
import {
  Clock,
  FileText,
  XCircle,
  AlertCircle,
  RotateCcw,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { EmptyState } from '@/components/empty-state';

interface Submissao {
  id: string;
  versao: number;
  estado: string;
  estado_label: string;
  parecer_diretor: string | null;
  data_submissao: string;
}

interface Justificativa {
  id: string;
  motivo: string;
  status: string;
  status_label: string;
}

interface PrazoAberto {
  id: string;
  prazo_id: string;
  titulo: string;
  data_limite: string;
  disciplina: { id: string; nome: string; sigla: string } | null;
  classe: { id: string; nome: string } | null;
  turma_id: string;
  turma_nome: string;
  ja_submeteu: boolean;
  bloqueado: boolean;
  foi_rejeitada: boolean;
  motivo_reenvio: string | null;
  submissao: Submissao | null;
  justificativa: Justificativa | null;
  url_submeter: string;
  url_justificar: string;
}

interface PrazosAbertosProps {
  prazos: PrazoAberto[];
}

export default function PrazosAbertos({ prazos = [] }: PrazosAbertosProps) {
  if (prazos.length === 0) {
    return (
      <EmptyState
        variant="table"
        icon={Clock}
        title="Nenhum prazo aberto"
        description="Não há prazos disponíveis para submeter no momento."
      />
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {prazos.map((prazo) => {
        const temJustificativaPendente =
          prazo.justificativa?.status === 'pendente';
        const foiRejeitada = prazo.foi_rejeitada && prazo.submissao;

        return (
          <Card
            key={prazo.id}
            className="transition-shadow hover:shadow-md"
          >
            <CardHeader>
              <CardTitle className="text-lg">
                <span className="line-clamp-2">{prazo.titulo}</span>
              </CardTitle>
              <CardDescription>
                <span className="flex items-center gap-1">
                  <Clock className="size-3" />
                  Limite: {prazo.data_limite}
                </span>
              </CardDescription>
            </CardHeader>

            <CardContent className="space-y-2 text-sm">
              <div>
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Disciplina
                </p>
                <p className="truncate font-medium">
                  {prazo.disciplina?.nome || 'Todas'}
                </p>
              </div>
              <div>
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Classe
                </p>
                <p className="truncate font-medium">
                  {prazo.classe?.nome || 'Todas'}
                </p>
              </div>
              <div>
                <p className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Turma
                </p>
                <p className="truncate font-semibold">
                  {prazo.turma_nome || '—'}
                </p>
              </div>

              {foiRejeitada && prazo.submissao && (
                <div className="mt-3 border bg-muted/50 p-3">
                  <div className="mb-2 flex items-center gap-2">
                    <XCircle className="size-4 shrink-0 text-muted-foreground" />
                    <p className="text-sm font-semibold">
                      Versão v{prazo.submissao.versao} rejeitada
                    </p>
                  </div>

                  <p className="text-xs text-muted-foreground">
                    <strong>Enviada em:</strong>{' '}
                    {prazo.submissao.data_submissao}
                  </p>

                  {prazo.submissao.parecer_diretor && (
                    <div className="mt-2 border bg-background p-2">
                      <p className="text-[11px] font-medium text-muted-foreground">
                        Parecer do diretor:
                      </p>
                      <p className="mt-0.5 text-xs italic text-muted-foreground">
                        "{prazo.submissao.parecer_diretor}"
                      </p>
                    </div>
                  )}

                  <p className="mt-2 text-[11px] font-medium text-muted-foreground">
                    Submeta uma nova versão corrigida.
                  </p>
                </div>
              )}

              {temJustificativaPendente && (
                <div className="mt-2 border bg-muted/50 p-2">
                  <p className="flex items-center gap-1 text-xs text-muted-foreground">
                    <AlertCircle className="size-3" />
                    <span>
                      <strong>Justificativa pendente.</strong> Aguarda
                      avaliação do diretor.
                    </span>
                  </p>
                </div>
              )}
            </CardContent>

            <CardFooter>
              {prazo.bloqueado ? (
                <Badge
                  variant="secondary"
                  className="w-full justify-center"
                >
                  <XCircle className="mr-1 size-3" />
                  Acesso negado
                </Badge>
              ) : foiRejeitada ? (
                <Button
                  asChild
                  className="w-full hover:cursor-pointer"
                >
                  <Link href={prazo.url_submeter || '#'}>
                    <RotateCcw className="mr-1 size-4" />
                    Submeter nova versão
                  </Link>
                </Button>
              ) : (
                <Button asChild className="w-full hover:cursor-pointer">
                  <Link href={prazo.url_submeter || '#'}>
                    <FileText className="mr-1 size-4" />
                    Submeter prova
                  </Link>
                </Button>
              )}
            </CardFooter>
          </Card>
        );
      })}
    </div>
  );
}