import { Download, FileText, Filter } from 'lucide-react';
import { useState } from 'react';

import { EmptyState } from '@/components/empty-state';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';

import { ModalEmitirDocumento } from './modal-emitir-documento';

export function DocumentoTable({ documentos, classes = [] }) {
  const [search, setSearch] = useState('');
  const [documentoActivo, setDocumentoActivo] = useState(null);
  const [modalOpen, setModalOpen] = useState(false);

  const lista = Array.isArray(documentos)
    ? documentos
    : (documentos?.data ?? []);
  const filtrados = lista.filter((documento) =>
    documento?.nome?.toLowerCase().includes(search.toLowerCase()),
  );

  const isEmpty = filtrados.length === 0;

  function handleEmitir(documento) {
    setDocumentoActivo(documento);
    setModalOpen(true);
  }

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Documentos</CardTitle>
          <CardDescription>
            Lista de documentos disponíveis para emissão
          </CardDescription>

          <CardAction className="flex gap-3">
            <Input
              placeholder="Digite para pesquisar..."
              className="w-64"
              value={search}
              onChange={(e) => setSearch(e.target.value)}
            />

            <Button variant="outline">
              <Filter />
              Filtrar
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0!">
          {isEmpty ? (
            <EmptyState
              variant="table"
              icon={FileText}
              title="Nenhum documento encontrado"
            />
          ) : (
            <Table>
              <TableHeader>
                <TableRow className="bg-muted/72">
                  <TableHead className="px-4">Nome</TableHead>
                  <TableHead className="px-4">Emolumento</TableHead>
                  <TableHead className="px-4">Âmbito</TableHead>
                  <TableHead className="px-4 text-right">Acções</TableHead>
                </TableRow>
              </TableHeader>

              <TableBody>
                {filtrados.map((documento) => (
                  <TableRow key={documento.id}>
                    <TableCell className="px-4 font-medium">
                      <div className="flex items-center gap-2">
                        {documento.nome}
                      </div>
                    </TableCell>

                    <TableCell className="px-4 font-medium">
                      {documento.valor
                        ? `${Number(documento.valor).toLocaleString('pt-AO')} AOA`
                        : '—'}
                    </TableCell>

                    <TableCell className="px-4">
                      {documento.curso_classe_id ? (
                        <Badge variant="outline">Classe específica</Badge>
                      ) : (
                        <Badge variant="outline">Geral</Badge>
                      )}
                    </TableCell>

                    <TableCell className="px-4 text-right">
                      <Button
                        size="sm"
                        variant="outline"
                        className="gap-1.5"
                        onClick={() => handleEmitir(documento)}
                      >
                        <Download className="h-3.5 w-3.5" />
                        Emitir
                      </Button>
                    </TableCell>
                  </TableRow>
                ))}
              </TableBody>
            </Table>
          )}
        </CardContent>
      </Card>

      <ModalEmitirDocumento
        documento={documentoActivo}
        classes={classes}
        open={modalOpen}
        onClose={() => setModalOpen(false)}
      />
    </>
  );
}
