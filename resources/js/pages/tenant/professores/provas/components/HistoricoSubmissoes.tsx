import { File, FileText, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import { EmptyState } from '@/components/empty-state';

interface SubmissaoHistorico {
  id: string;
  prazo: string;
  disciplina: string;
  classe: string | null;
  turma_nome: string;
  versao: number;
  estado: string;
  estado_label: string;
  badge_class: string;
  data_submissao: string;
  parecer: string | null;
  url_prova: string;
  url_chave: string;
}

interface HistoricoSubmissoesProps {
  historico: {
    data: SubmissaoHistorico[];
    links: any[];
    current_page: number;
    last_page: number;
  };
  onPageChange: (page: number) => void;
}

export default function HistoricoSubmissoes({
  historico,
  onPageChange,
}: HistoricoSubmissoesProps) {
  const isEmpty = !historico?.data || historico.data.length === 0;

  return (
    <Card className="gap-0 pb-0">
      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Clock}
            title="Sem histórico"
            description="Nenhuma submissão registrada até o momento."
          />
        ) : (
          <div className="w-full overflow-x-auto">
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="min-w-48 px-4">Prazo</TableHead>
                  <TableHead className="min-w-40 px-4">Disciplina</TableHead>
                  <TableHead className="min-w-32 px-4">Turma</TableHead>
                  <TableHead className="min-w-20 px-4 text-center">
                    Versão
                  </TableHead>
                  <TableHead className="min-w-40 px-4 text-center">
                    Estado
                  </TableHead>
                  <TableHead className="min-w-32 px-4 text-center">
                    Data
                  </TableHead>
                  <TableHead className="min-w-40 px-4 text-center">
                    Ações
                  </TableHead>
                </TableRow>
              </TableHeader>
              <TableBody>
                {historico.data.map((sub) => (
                  <TableRow key={sub.id}>
                    <TableCell className="px-4 font-medium">
                      <span className="line-clamp-2">{sub.prazo}</span>
                    </TableCell>
                    <TableCell className="px-4">
                      <span className="line-clamp-1">{sub.disciplina}</span>
                    </TableCell>
                    <TableCell className="px-4">
                      <span className="line-clamp-1">
                        {sub.turma_nome || sub.classe || 'N/A'}
                      </span>
                    </TableCell>
                    <TableCell className="px-4 text-center font-mono text-xs tabular-nums">
                      v{sub.versao}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      <Badge variant="outline">{sub.estado_label}</Badge>
                      {sub.parecer && (
                        <div
                          className="mt-1 line-clamp-2 text-xs text-muted-foreground"
                          title={sub.parecer}
                        >
                          {sub.parecer}
                        </div>
                      )}
                    </TableCell>
                    <TableCell className="px-4 text-center text-xs whitespace-nowrap">
                      {sub.data_submissao}
                    </TableCell>
                    <TableCell className="px-4 text-center">
                      <div className="flex justify-center gap-1">
                        <Button
                          variant="outline"
                          size="sm"
                          asChild
                          className="hover:cursor-pointer"
                        >
                          <a
                            href={sub.url_prova}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            <File className="mr-1 size-4" />
                            Prova
                          </a>
                        </Button>
                        <Button
                          variant="outline"
                          size="sm"
                          asChild
                          className="hover:cursor-pointer"
                        >
                          <a
                            href={sub.url_chave}
                            target="_blank"
                            rel="noopener noreferrer"
                          >
                            <FileText className="mr-1 size-4" />
                            Chave
                          </a>
                        </Button>
                      </div>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          </div>
        )}
      </CardContent>
    </Card>
  );
}