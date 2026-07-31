import { Link, router } from '@inertiajs/react';
import { LayersIcon, MoreHorizontalIcon, FilterIcon } from 'lucide-react';
import { EmptyState } from '@/components/empty-state';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import TablePagination from '@/components/table-pagination';
import { create, show, destroy } from '@/actions/App/Http/Controllers/PagamentoController';
import { DropdownMenuSeparator } from '@radix-ui/react-dropdown-menu';
import { useState } from 'react';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const formatCurrency = (value) => {
  const amount = Number(value ?? 0);
  return Number.isNaN(amount)
    ? '—'
    : `${amount.toLocaleString('pt', { minimumFractionDigits: 2, maximumFractionDigits: 2 })} AOA`;
};

const metodoLabels = {
  dinheiro: 'Dinheiro',
  transferencia: 'Transferência',
  multicaixa: 'Multicaixa',
  outro: 'Outro',
};

export default function PagamentosTable({
  pagamentos = [],
  can,
  deleteFn,
  pagination = {},
  onPageChange,
  filtros = {},
  cursosClasses = [],
  onFilterChange,
}) {
  const isEmpty = pagamentos.length === 0;

  // Estado local para os filtros (se não vier do pai)
  const [localFiltros, setLocalFiltros] = useState({
    aluno_id: filtros.aluno_id || '',
    data_inicio: filtros.data_inicio || '',
    data_fim: filtros.data_fim || '',
    curso_classe_id: filtros.curso_classe_id || '',
  });

  const handleFilterChange = (key, value) => {
    const newFiltros = { ...localFiltros, [key]: value };
    setLocalFiltros(newFiltros);
    if (onFilterChange) {
      onFilterChange(newFiltros);
    }
  };

  const aplicarFiltros = () => {
    // Aplica os filtros via router ou via callback
    if (onFilterChange) {
      onFilterChange(localFiltros);
    }
  };

  const limparFiltros = () => {
    const empty = { aluno_id: '', data_inicio: '', data_fim: '', curso_classe_id: '' };
    setLocalFiltros(empty);
    if (onFilterChange) {
      onFilterChange(empty);
    }
  };

  return (
    <Card className="mx-auto w-full max-w-7xl gap-0">
      <CardHeader className="border-b">
        <CardTitle>Pagamentos</CardTitle>
        <CardDescription>
          Registos de propinas e outros encargos escolares.
        </CardDescription>

        <CardAction className="flex flex-wrap items-center gap-2">
          {/* Filtros */}
          <div className="flex flex-wrap items-center gap-2">
            <div className="flex items-center gap-1">
              <Label htmlFor="aluno_id" className="sr-only">Aluno</Label>
              <Input
                id="aluno_id"
                placeholder="Aluno (ID)"
                value={localFiltros.aluno_id}
                onChange={(e) => handleFilterChange('aluno_id', e.target.value)}
                className="w-40"
              />
            </div>

            <div className="flex items-center gap-1">
              <Label htmlFor="data_inicio" className="sr-only">Data início</Label>
              <Input
                id="data_inicio"
                type="date"
                value={localFiltros.data_inicio}
                onChange={(e) => handleFilterChange('data_inicio', e.target.value)}
                className="w-36"
              />
            </div>

            <div className="flex items-center gap-1">
              <Label htmlFor="data_fim" className="sr-only">Data fim</Label>
              <Input
                id="data_fim"
                type="date"
                value={localFiltros.data_fim}
                onChange={(e) => handleFilterChange('data_fim', e.target.value)}
                className="w-36"
              />
            </div>

            <div className="flex items-center gap-1">
              <Label htmlFor="curso_classe_id" className="sr-only">Classe</Label>
              <Select
                value={localFiltros.curso_classe_id}
                onValueChange={(value) => handleFilterChange('curso_classe_id', value)}
              >
                <SelectTrigger className="w-48">
                  <SelectValue placeholder="Todas as classes" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="">Todas</SelectItem>
                  {cursosClasses.map((cc) => (
                    <SelectItem key={cc.id} value={cc.id}>
                      {cc.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>

            <Button variant="outline" size="sm" onClick={aplicarFiltros}>
              <FilterIcon className="mr-1 size-3" /> Filtrar
            </Button>
            <Button variant="ghost" size="sm" onClick={limparFiltros}>
              Limpar
            </Button>
          </div>

          {can?.create && (
            <Button asChild>
              <Link href={create().url}>Registar</Link>
            </Button>
          )}
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={LayersIcon}
            title="Nenhum pagamento registado"
            description="Comece por registar o primeiro pagamento."
            action={
              can?.create
                ? {
                    label: 'Registar pagamento',
                    href: create().url,
                    variant: 'outline',
                  }
                : undefined
            }
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Aluno</TableHead>
                <TableHead className="px-4">Classe</TableHead>
                <TableHead className="px-4">Método</TableHead>
                <TableHead className="px-4">Total</TableHead>
                <TableHead className="px-4">Realizado em</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {pagamentos.map((p) => (
                <TableRow
                  key={p.id}
                  className="cursor-pointer"
                  onClick={() => router.visit(show(p.id).url)}
                >
                  <TableCell className="px-4 font-medium">{p.aluno}</TableCell>
                  <TableCell className="px-4">
                    <Badge variant="outline">{p.classes || 'Geral'}</Badge>
                  </TableCell>
                  <TableCell className="px-4">
                    <Badge variant="secondary">
                      {metodoLabels[p.metodo] ?? p.metodo}
                    </Badge>
                  </TableCell>
                  <TableCell className="px-4 font-medium">
                    {formatCurrency(p.valor_total)}
                  </TableCell>
                  <TableCell className="px-4 text-muted-foreground">
                    {p.data_pagamento}
                  </TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger
                        asChild
                        onClick={(e) => e.stopPropagation()}
                      >
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                          <span className="sr-only">Abrir menu</span>
                        </Button>
                      </DropdownMenuTrigger>

                      <DropdownMenuContent align="end">
                        <DropdownMenuItem
                          onClick={(e) => {
                            e.stopPropagation();
                            router.visit(show(p.id).url);
                          }}
                        >
                          Ver detalhes
                        </DropdownMenuItem>

                        <DropdownMenuSeparator />

                        <DropdownMenuItem
                          variant="destructive"
                          onClick={(e) => {
                            e.stopPropagation();
                            deleteFn(p.id);
                          }}
                        >
                          Anular
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>

      <TablePagination pagination={pagination} onPageChange={onPageChange} />
    </Card>
  );
}