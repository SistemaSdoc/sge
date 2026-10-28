import { Link } from '@inertiajs/react';
import {
  Clock,
  MessageSquare,
  CheckCircle,
  XCircle,
  Send,
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

interface PrazoEncerrado {
  id: string;
  prazo_id: string;
  titulo: string;
  data_limite: string;
  status: string;
  status_label: string;
  disciplina: { id: string; nome: string; sigla: string } | null;
  classe: { id: string; nome: string } | null;
  submeteu: boolean;
  turma_nome: string;
  bloqueado: boolean;
  aceite: boolean;
  pode_submeter: boolean;
  motivo_reenvio: string | null;
  prazo_extra_ate: string | null;
  dias_restantes: number | null;
  submissao: Submissao | null;
  justificativa: Justificativa | null;
  url_submeter: string | null;
  url_justificar: string;
}

interface PrazosEncerradosProps {
  prazos: PrazoEncerrado[];
}

export default function PrazosEncerrados({
  prazos = [],
}: PrazosEncerradosProps) {
  if (prazos.length === 0) {
    return (
      <EmptyState
        variant="table"
        icon={Clock}
        title="Nenhum prazo encerrado"
        description="Não há prazos encerrados no momento."
      />
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {prazos.map((prazo) => {
        const jaJustificou = !!prazo.justificativa;
        const podeSubmeter = prazo.pode_submeter === true;
        const foiRejeitada = prazo.submissao?.estado === 'rejeitado';
        const foiAprovada = prazo.submissao?.estado === 'aprovado';
        const aguardaAvaliacao = prazo.submissao?.estado === 'pendente';

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
                  Encerrado em: {prazo.data_limite}
                </span>
                <Badge variant="outline" className="ml-2">
                  {prazo.status_label}
                </Badge>
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

                  {podeSubmeter ? (
                    <p className="mt-2 text-[11px] font-medium text-muted-foreground">
                      Submeta uma nova versão corrigida.
                    </p>
                  ) : (
                    <p className="mt-2 text-[11px] font-medium text-muted-foreground">
                      O prazo terminou. Contacte o diretor para reabrir.
                    </p>
                  )}
                </div>
              )}

              {foiAprovada && (
                <div className="mt-2 flex items-center gap-2 border bg-muted/50 p-2 text-xs">
                  <CheckCircle className="size-3 shrink-0 text-muted-foreground" />
                  <span className="text-muted-foreground">
                    Versão v{prazo.submissao?.versao} aprovada
                  </span>
                </div>
              )}

              {aguardaAvaliacao && (
                <div className="mt-2 flex items-center gap-2 border bg-muted/50 p-2 text-xs">
                  <AlertCircle className="size-3 shrink-0 text-muted-foreground" />
                  <span className="text-muted-foreground">
                    Versão v{prazo.submissao?.versao} aguarda avaliação
                  </span>
                </div>
              )}

              {jaJustificou && !prazo.submeteu && (
                <div className="mt-2 border bg-muted/30 p-2 text-xs">
                  <p className="line-clamp-2">
                    <strong>Justificativa:</strong>{' '}
                    {prazo.justificativa?.motivo}
                  </p>
                  <Badge variant="outline" className="mt-1">
                    {prazo.justificativa?.status_label}
                  </Badge>
                </div>
              )}

              {prazo.aceite && !prazo.submeteu && (
                <div className="mt-2 border bg-muted/50 p-2">
                  <p className="flex items-center gap-1 text-xs font-medium text-muted-foreground">
                    <CheckCircle className="size-3" />
                    Justificativa aceita
                  </p>
                  {prazo.prazo_extra_ate && (
                    <p className="mt-1 text-[11px] text-muted-foreground">
                      Prazo até <strong>{prazo.prazo_extra_ate}</strong>
                      {prazo.dias_restantes !== null && (
                        <span className="ml-1">
                          ({prazo.dias_restantes}{' '}
                          {prazo.dias_restantes === 1 ? 'dia' : 'dias'})
                        </span>
                      )}
                    </p>
                  )}
                </div>
              )}

              {prazo.justificativa?.status === 'aceita' &&
                !prazo.aceite &&
                !prazo.submeteu && (
                  <div className="mt-2 border bg-muted/40 p-2">
                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                      <Clock className="size-3" />
                      <span>
                        <strong>Prazo extra expirado.</strong> Contacte o
                        diretor.
                      </span>
                    </p>
                  </div>
                )}

              {prazo.bloqueado && (
                <div className="mt-2 border bg-muted/50 p-2">
                  <p className="flex items-center gap-1 text-xs text-muted-foreground">
                    <XCircle className="size-3" />
                    <span>
                      <strong>Justificativa recusada.</strong> Não pode
                      submeter.
                    </span>
                  </p>
                </div>
              )}

              {prazo.justificativa?.status === 'pendente' &&
                !prazo.submeteu && (
                  <div className="mt-2 border bg-muted/50 p-2">
                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                      <AlertCircle className="size-3" />
                      <span>
                        <strong>Justificativa pendente.</strong> Aguarda
                        avaliação.
                      </span>
                    </p>
                  </div>
                )}
            </CardContent>

            <CardFooter>
              {prazo.bloqueado ? (
                <Badge variant="outline" className="w-full justify-center">
                  <XCircle className="mr-1 size-3" />
                  Acesso negado
                </Badge>
              ) : podeSubmeter && foiRejeitada ? (
                <Button asChild className="w-full hover:cursor-pointer">
                  <Link href={prazo.url_submeter || '#'}>
                    <RotateCcw className="mr-1 size-4" />
                    Submeter nova versão
                  </Link>
                </Button>
              ) : podeSubmeter ? (
                <Button asChild className="w-full hover:cursor-pointer">
                  <Link href={prazo.url_submeter || '#'}>
                    <Send className="mr-1 size-4" />
                    Submeter prova
                  </Link>
                </Button>
              ) : foiAprovada ? (
                <Badge
                  variant="secondary"
                  className="w-full justify-center"
                >
                  <CheckCircle className="mr-1 size-3" />
                  Submeteu e foi aprovada
                </Badge>
              ) : foiRejeitada ? (
                <Badge variant="outline" className="w-full justify-center">
                  <XCircle className="mr-1 size-3" />
                  Rejeitada — sem reenvio
                </Badge>
              ) : aguardaAvaliacao ? (
                <Badge variant="secondary" className="w-full justify-center">
                  <Clock className="mr-1 size-3" />
                  Submeteu (em avaliação)
                </Badge>
              ) : jaJustificou ? (
                <Badge variant="secondary" className="w-full justify-center">
                  <CheckCircle className="mr-1 size-3" />
                  Justificativa enviada
                </Badge>
              ) : (
                <Button
                  variant="outline"
                  asChild
                  className="w-full hover:cursor-pointer"
                >
                  <Link href={prazo.url_justificar}>
                    <MessageSquare className="mr-1 size-4" />
                    Justificar não submissão
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