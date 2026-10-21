import { Link } from '@inertiajs/react';
import {
  Clock,
  BookOpen,
  Users,
  MessageSquare,
  CheckCircle,
  XCircle,
  GraduationCap,
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
        description="Não há prazos encerrados de momento."
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
            className={`transition-shadow hover:shadow-md ${
              foiRejeitada && !podeSubmeter
                ? 'border-red-300 dark:border-red-800'
                : ''
            }`}
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

            <CardContent className="space-y-1 text-sm">
              <p className="flex items-center gap-2">
                <BookOpen className="size-4 shrink-0 text-muted-foreground" />
                <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Disciplina
                </span>
                <span className="truncate font-medium">
                  {prazo.disciplina?.nome || 'Todas'}
                </span>
              </p>
              <p className="flex items-center gap-2">
                <GraduationCap className="size-4 shrink-0 text-muted-foreground" />
                <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Classe
                </span>
                <span className="truncate font-medium">
                  {prazo.classe?.nome || 'Todas'}
                </span>
              </p>
              <p className="flex items-center gap-2">
                <Users className="size-4 shrink-0 text-primary" />
                <span className="text-[11px] font-medium uppercase tracking-wide text-muted-foreground">
                  Turma
                </span>
                <span className="truncate font-semibold text-primary">
                  {prazo.turma_nome || '—'}
                </span>
              </p>

              {foiRejeitada && prazo.submissao && (
                <div className="mt-3 border-2 border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-950/20">
                  <div className="mb-2 flex items-center gap-2">
                    <XCircle className="size-4 text-red-600 dark:text-red-400" />
                    <p className="text-sm font-semibold text-red-700 dark:text-red-400">
                      Versão v{prazo.submissao.versao} rejeitada
                    </p>
                  </div>

                  <p className="text-xs text-red-700 dark:text-red-300">
                    <strong>Enviada em:</strong>{' '}
                    {prazo.submissao.data_submissao}
                  </p>

                  {prazo.submissao.parecer_diretor && (
                    <div className="mt-2 border border-red-200 bg-white/60 p-2 dark:border-red-800 dark:bg-red-950/40">
                      <p className="text-[11px] font-medium text-red-800 dark:text-red-300">
                        Parecer do director:
                      </p>
                      <p className="mt-0.5 text-xs italic text-red-700 dark:text-red-200">
                        "{prazo.submissao.parecer_diretor}"
                      </p>
                    </div>
                  )}

                  {podeSubmeter ? (
                    <p className="mt-2 text-[11px] font-medium text-red-700 dark:text-red-300">
                      Submeta uma nova versão corrigida.
                    </p>
                  ) : (
                    <p className="mt-2 text-[11px] font-medium text-red-700 dark:text-red-300">
                      O prazo terminou. Contacte o director para reabrir.
                    </p>
                  )}
                </div>
              )}

              {foiAprovada && (
                <div className="mt-2 flex items-center gap-2 border border-green-200 bg-green-50 p-2 text-xs dark:border-green-800 dark:bg-green-950/20">
                  <CheckCircle className="size-3 shrink-0 text-green-600" />
                  <span className="text-green-700 dark:text-green-400">
                    Versão v{prazo.submissao?.versao} aprovada
                  </span>
                </div>
              )}

              {aguardaAvaliacao && (
                <div className="mt-2 flex items-center gap-2 border border-amber-200 bg-amber-50 p-2 text-xs dark:border-amber-800 dark:bg-amber-950/20">
                  <AlertCircle className="size-3 shrink-0 text-amber-600" />
                  <span className="text-amber-700 dark:text-amber-400">
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
                <div className="mt-2 border border-green-200 bg-green-50 p-2 dark:border-green-800 dark:bg-green-950/20">
                  <p className="flex items-center gap-1 text-xs font-medium text-green-700 dark:text-green-400">
                    <CheckCircle className="size-3" />
                    Justificativa aceite
                  </p>
                  {prazo.prazo_extra_ate && (
                    <p className="mt-1 text-[11px] text-green-600 dark:text-green-300">
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
                  <div className="mt-2 border bg-muted/40 p-2 dark:bg-muted/20">
                    <p className="flex items-center gap-1 text-xs text-muted-foreground">
                      <Clock className="size-3" />
                      <span>
                        <strong>Prazo extra expirado.</strong> Contacte o
                        director.
                      </span>
                    </p>
                  </div>
                )}

              {prazo.bloqueado && (
                <div className="mt-2 border border-red-200 bg-red-50 p-2 dark:border-red-800 dark:bg-red-950/20">
                  <p className="flex items-center gap-1 text-xs text-red-700 dark:text-red-400">
                    <XCircle className="size-3" />
                    <span>
                      <strong>Justificativa recusada.</strong> Não pode submeter.
                    </span>
                  </p>
                </div>
              )}

              {prazo.justificativa?.status === 'pendente' &&
                !prazo.submeteu && (
                  <div className="mt-2 border border-amber-200 bg-amber-50 p-2 dark:border-amber-800 dark:bg-amber-950/20">
                    <p className="flex items-center gap-1 text-xs text-amber-700 dark:text-amber-400">
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
                <Badge
                  variant="destructive"
                  className="w-full justify-center"
                >
                  <XCircle className="mr-1 size-3" />
                  Acesso negado
                </Badge>
              ) : podeSubmeter && foiRejeitada ? (
                <Button
                  asChild
                  className="w-full bg-red-600 hover:bg-red-700 hover:cursor-pointer"
                >
                  <Link href={prazo.url_submeter || '#'}>
                    <RotateCcw className="mr-1 size-4" />
                    Submeter nova versão
                  </Link>
                </Button>
              ) : podeSubmeter ? (
                <Button
                  asChild
                  className="w-full bg-blue-600 hover:bg-blue-700 hover:cursor-pointer"
                >
                  <Link href={prazo.url_submeter || '#'}>
                    <Send className="mr-1 size-4" />
                    Submeter prova
                  </Link>
                </Button>
              ) : foiAprovada ? (
                <Badge
                  variant="default"
                  className="w-full justify-center bg-green-600"
                >
                  <CheckCircle className="mr-1 size-3" />
                  Submeteu e foi aprovada
                </Badge>
              ) : foiRejeitada ? (
                <Badge
                  variant="destructive"
                  className="w-full justify-center"
                >
                  <XCircle className="mr-1 size-3" />
                  Rejeitada — sem reenvio
                </Badge>
              ) : aguardaAvaliacao ? (
                <Badge
                  variant="secondary"
                  className="w-full justify-center"
                >
                  <Clock className="mr-1 size-3" />
                  Submeteu (em avaliação)
                </Badge>
              ) : jaJustificou ? (
                <Badge
                  variant="secondary"
                  className="w-full justify-center"
                >
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