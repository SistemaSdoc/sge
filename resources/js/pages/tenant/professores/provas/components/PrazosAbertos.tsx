import { Link } from '@inertiajs/react';
import {
  Clock,
  BookOpen,
  Users,
  FileText,
  XCircle,
  AlertCircle,
  GraduationCap,
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
            className={`transition-shadow hover:shadow-md ${
              foiRejeitada ? 'border-red-300 dark:border-red-800' : ''
            }`}
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
                      <p className="mt-0.5 text-xs text-red-700 italic dark:text-red-200">
                        "{prazo.submissao.parecer_diretor}"
                      </p>
                    </div>
                  )}

                  <p className="mt-2 text-[11px] font-medium text-red-700 dark:text-red-300">
                    Submeta uma nova versão corrigida.
                  </p>
                </div>
              )}

              {temJustificativaPendente && (
                <div className="mt-2 border border-amber-200 bg-amber-50 p-2 dark:border-amber-800 dark:bg-amber-950/20">
                  <p className="flex items-center gap-1 text-xs text-amber-700 dark:text-amber-400">
                    <AlertCircle className="size-3" />
                    <span>
                      <strong>Justificativa pendente.</strong> Aguarda avaliação
                      do director.
                    </span>

                </div>
              )}
            </CardContent>

                  className="w-full justify-center"
                >
                  <XCircle className="mr-1 size-3" />
                  Acesso negado
                </Badge>
              ) : foiRejeitada ? (
                <Button
                  asChild
                  className="w-full bg-red-600 hover:bg-red-700 hover:cursor-pointer"
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
