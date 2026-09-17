import { Link } from '@inertiajs/react';
import { CheckCircle, Clock, BookOpen, Users, FileText, XCircle } from 'lucide-react';
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

interface PrazoAberto {
  id: string;
  titulo: string;
  data_limite: string;
  disciplina: { id: string; nome: string; sigla: string } | null;
  classe: { id: string; nome: string } | null;
  turma_nome: string;
  ja_submeteu: boolean;
  bloqueado: boolean;
  url_submeter: string;
}

interface PrazosAbertosProps {
  prazos: PrazoAberto[];
}

export default function PrazosAbertos({ prazos = [] }: PrazosAbertosProps) {
  if (prazos.length === 0) {
    return (
      <div className="flex items-center gap-2 text-muted-foreground border rounded-lg p-4 bg-muted/30">
        <Clock className="size-5" />
        <span>Nenhum prazo aberto no momento.</span>
      </div>
    );
  }

  return (
    <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
      {prazos.map((prazo) => (
        <Card key={prazo.id} className="shadow-sm border-border hover:shadow-md transition-shadow">
          <CardHeader>
            <CardTitle className="text-lg">{prazo.titulo}</CardTitle>
            <CardDescription>
              <span className="flex items-center gap-1">
                <Clock className="size-3" />
                Limite: {prazo.data_limite}
              </span>
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
              <strong>Turma:</strong> {prazo.turma_nome || prazo.classe?.nome || 'Todas'}
            </p>
            {prazo.bloqueado && (
              <div className="mt-2 p-2 bg-red-50 dark:bg-red-950/20 rounded border border-red-200 dark:border-red-800">
                <p className="text-xs text-red-700 dark:text-red-400 flex items-center gap-1">
                  <XCircle className="size-3" />
                  <strong>Justificativa recusada.</strong> Não pode submeter.
                </p>
              </div>
            )}
          </CardContent>
          <CardFooter className="flex flex-col gap-2">
            {prazo.bloqueado ? (
              <Badge variant="destructive" className="w-full justify-center">
                <XCircle className="size-3 mr-1" />
                Acesso negado
              </Badge>
            ) : !prazo.ja_submeteu ? (
              <Button asChild className="w-full">
                <Link href={prazo.url_submeter || '#'}>
                  <FileText className="size-4 mr-1" />
                  Submeter Prova
                </Link>
              </Button>
            ) : (
              <Badge variant="default" className="w-full justify-center">
                <CheckCircle className="size-3 mr-1" />
                Já submeteu
              </Badge>
            )}
          </CardFooter>
        </Card>
      ))}
    </div>
  );
}