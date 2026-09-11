import { Link } from '@inertiajs/react';
import { Clock, BookOpen, Users, MessageSquare, CheckCircle, XCircle } from 'lucide-react';
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

interface Justificativa {
  id: string;
  motivo: string;
  status: string;
  status_label: string;
}

interface PrazoEncerrado {
  id: string;
  titulo: string;
  data_limite: string;
  status: string;
  status_label: string;
  disciplina: { id: string; nome: string; sigla: string } | null;
  classe: { id: string; nome: string } | null;
  submeteu: boolean;
  turma_nome: string;
  bloqueado: boolean; 
  justificativa: Justificativa | null;
  url_justificar: string;
}

interface PrazosEncerradosProps {
  prazos: PrazoEncerrado[];
}

export default function PrazosEncerrados({ prazos = [] }: PrazosEncerradosProps) {
  if (prazos.length === 0) {
    return (
      <div className="flex items-center gap-2 text-muted-foreground border rounded-lg p-4 bg-muted/30">
        <Clock className="size-5" />
        <span>Nenhum prazo encerrado.</span>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {prazos.map((prazo) => {
        const jaJustificou = !!prazo.justificativa;

        return (
          <Card key={prazo.id} className="shadow-sm border-border hover:shadow-md transition-shadow">
            <CardHeader>
              <CardTitle className="text-lg">{prazo.titulo}</CardTitle>
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
                <BookOpen className="size-4 text-muted-foreground" />
                <strong>Disciplina:</strong> {prazo.disciplina?.nome || 'Todas'}
              </p>
               <p className="flex items-center gap-2">
                <BookOpen className="size-4 text-muted-foreground" />
              <strong>Classe:</strong> {prazo.classe?.nome || 'Todas'}
            </p>
              <p className="flex items-center gap-2">
                <Users className="size-4 text-muted-foreground" />
                <strong>Turma:</strong> {prazo.turma_nome || 'Todas'}
              </p>
              {prazo.justificativa && (
                <div className="mt-2 p-2 bg-muted/30 rounded text-xs">
                  <strong>Justificativa:</strong> {prazo.justificativa.motivo}
                  <br />
                  <Badge variant="outline" className="mt-1">
                    {prazo.justificativa.status_label}
                  </Badge>
                </div>
              )}
              {prazo.bloqueado && (
                <div className="mt-2 p-2 bg-red-50 dark:bg-red-950/20 rounded border border-red-200 dark:border-red-800">
                  <p className="text-xs text-red-700 dark:text-red-400 flex items-center gap-1">
                    <XCircle className="size-3" />
                    <strong>Justificativa recusada.</strong> Não pode justificar.
                  </p>
                </div>
              )}
            </CardContent>
            <CardFooter>
              {prazo.bloqueado ? (
                <Badge variant="destructive" className="w-full justify-center">
                  <XCircle className="size-3 mr-1" />
                  Acesso negado
                </Badge>
              ) : !prazo.submeteu ? (
                !jaJustificou ? (
                  <Button variant="outline" asChild className="w-full">
                    <Link href={prazo.url_justificar || '#'}>
                      <MessageSquare className="size-4 mr-1" />
                      Justificar não submissão
                    </Link>
                  </Button>
                ) : (
                  <Badge variant="secondary" className="w-full justify-center">
                    <CheckCircle className="size-3 mr-1" />
                    Justificativa enviada
                  </Badge>
                )
              ) : (
                <Badge variant="default" className="w-full justify-center bg-green-600">
                  <CheckCircle className="size-3 mr-1" />
                  Submeteu
                </Badge>
              )}
            </CardFooter>
          </Card>
        );
      })}
    </div>
  );
}