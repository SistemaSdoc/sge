import { File, FileText, Clock } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

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

export default function HistoricoSubmissoes({ historico, onPageChange }: HistoricoSubmissoesProps) {
  if (historico.data.length === 0) {
    return (
      <div className="flex items-center gap-2 text-muted-foreground border rounded-lg p-4 bg-muted/30">
        <Clock className="size-5" />
        <span>Nenhuma submissão no histórico.</span>
      </div>
    );
  }

  return (
    <div className="rounded-lg border border-border shadow-sm overflow-hidden bg-card">
      <div className="overflow-x-auto">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/50">
              <TableHead className="px-4">Prazo</TableHead>
              <TableHead className="px-4">Disciplina</TableHead>
              <TableHead className="px-4">Turma</TableHead> 
              <TableHead className="px-4 text-center">Versão</TableHead>
              <TableHead className="px-4 text-center">Estado</TableHead>
              <TableHead className="px-4 text-center">Data</TableHead>
              <TableHead className="px-4 text-center">Ações</TableHead>
            </TableRow>
          </TableHeader>
          <TableBody>
            {historico.data.map((sub) => (
              <TableRow key={sub.id}>
                <TableCell className="px-4 font-medium">{sub.prazo}</TableCell>
                <TableCell className="px-4">{sub.disciplina}</TableCell>
                <TableCell className="px-4">{sub.turma_nome || sub.classe || 'N/A'}</TableCell>
                <TableCell className="px-4 text-center">{sub.versao}</TableCell>
                <TableCell className="px-4 text-center">
                  <Badge variant="outline" className={sub.badge_class}>
                    {sub.estado_label}
                  </Badge>
                  {sub.parecer && (
                    <div className="text-xs text-muted-foreground mt-1">{sub.parecer}</div>
                  )}
                </TableCell>
                <TableCell className="px-4 text-center">{sub.data_submissao}</TableCell>
                <TableCell className="px-4 text-center">
                  <div className="flex justify-center gap-1">
                    <Button variant="outline" size="sm" asChild>
                      <a href={sub.url_prova} target="_blank" rel="noopener noreferrer">
                        <File className="mr-1 size-4" />
                        Prova
                      </a>
                    </Button>
                    <Button variant="outline" size="sm" asChild>
                      <a href={sub.url_chave} target="_blank" rel="noopener noreferrer">
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
    </div>
  );
}