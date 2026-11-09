import { ChevronDown } from 'lucide-react';
import { TableSearch } from '@/components/table-search';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { Card, CardDescription, CardHeader } from '@/components/ui/card';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Separator } from '@/components/ui/separator';
import { exportarLista } from '@/routes/tenant/dashboard/grupos-pap';

const normalizeFilterValue = (value) =>
  value === null || value === undefined || value === '' ? '' : String(value);

export function Header({
  can = {},
  instituicao,
  instituicoes = [],
  cursosTutelados = [],
  filtroInstituicao,
  onInstituicaoChange,
  filtroCurso,
  onCursoChange,
  anosLectivos = [],
  anoLectivoId,
  onAnoLectivoChange,
  onAddGrupo,
  exportFilters,
  hasGroups = false,
  search,
  onSearchChange,
  onSearchSubmit,
}) {
  const instituicaoSeleccionada =
    instituicoes.find(
      (item) =>
        normalizeFilterValue(item.id) ===
        normalizeFilterValue(filtroInstituicao),
    ) ?? instituicao;

  const isColegioSelecionado =
    can?.selecionarInstituicao &&
    filtroInstituicao &&
    filtroInstituicao !== 'todas' &&
    filtroInstituicao !== String(instituicao?.id ?? '');
  const canExport = Boolean(
    hasGroups && filtroInstituicao && filtroCurso && anoLectivoId,
  );

  const handleExportarLista = (formato) => {
    window.location.assign(
      exportarLista({
        query: {
          instituicao_id: filtroInstituicao,
          curso_id: filtroCurso,
          ano_lectivo_id: anoLectivoId,
          search: exportFilters?.search,
          formato,
        },
      }).url,
    );
  };

  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        {/* Segue o mesmo padrão do Header do curso */}
        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
          <div className="min-w-0 space-y-1">
            <Breadcrumb>
              <BreadcrumbList className="flex-wrap">
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbLink asChild>
                    <span className="line-clamp-2 text-sm font-semibold text-primary">
                      {instituicaoSeleccionada.nome}
                    </span>
                  </BreadcrumbLink>
                </BreadcrumbItem>
                <BreadcrumbSeparator />
                <BreadcrumbItem className="min-w-0">
                  <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                    Grupos para Prova de Aptidão Profissional
                  </BreadcrumbPage>
                </BreadcrumbItem>
              </BreadcrumbList>
            </Breadcrumb>

            <CardDescription>
              Grupos criados para a Prova de Aptidão Profissional (PAP) da
              instituição{' '}
              <span className="font-bold">{instituicaoSeleccionada.nome}</span>
            </CardDescription>
          </div>

          <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
            {can?.create && !isColegioSelecionado && (
              <Button
                size="sm"
                className="w-full justify-center hover:cursor-pointer sm:w-auto"
                onClick={onAddGrupo}
              >
                Adicionar grupo
              </Button>
            )}
            <DropdownMenu>
              <DropdownMenuTrigger asChild className="hover:cursor-pointer">
                <Button
                  size="sm"
                  variant="outline"
                  className="w-full justify-center sm:w-auto"
                  disabled={!canExport}
                  title={
                    hasGroups
                      ? 'Escolher formato de exportação'
                      : 'Não existem grupos para os filtros selecionados'
                  }
                >
                  Exportar
                  <ChevronDown />
                </Button>
              </DropdownMenuTrigger>

              <DropdownMenuContent align="end">
                <DropdownMenuItem
                  onSelect={() => handleExportarLista('pdf')}
                  className="hover:cursor-pointer"
                >
                  Exportar PDF
                </DropdownMenuItem>

                <Separator />

                <DropdownMenuItem
                  onSelect={() => handleExportarLista('excel')}
                  className="hover:cursor-pointer"
                >
                  Exportar Excel
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>
      </CardHeader>

      <div className="flex flex-col gap-3 overflow-hidden px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
        <h2 className="text-sm font-semibold whitespace-nowrap">Filtros</h2>

        <div className="flex w-full flex-col justify-end gap-2 sm:w-auto sm:flex-row">
          {can?.selecionarInstituicao && (
            <Select
              value={
                normalizeFilterValue(filtroInstituicao) ||
                (can?.selecionarTodasInstituicoes ? 'todas' : '')
              }
              onValueChange={onInstituicaoChange}
            >
              <SelectTrigger className="w-full sm:w-56">
                <SelectValue placeholder="Instituição" />
              </SelectTrigger>
              <SelectContent>
                {can?.selecionarTodasInstituicoes && (
                  <SelectItem value="todas">Todas as instituições</SelectItem>
                )}
                {instituicoes.map((item) => (
                  <SelectItem key={item.id} value={String(item.id)}>
                    {item.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}

          {cursosTutelados.length > 0 && (
            <Select
              value={normalizeFilterValue(filtroCurso) || 'todos'}
              onValueChange={onCursoChange}
            >
              <SelectTrigger className="w-full sm:w-56">
                <SelectValue placeholder="Todos os cursos" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="todos">Todos os cursos</SelectItem>
                {cursosTutelados.map((curso) => (
                  <SelectItem key={curso.id} value={String(curso.id)}>
                    {curso.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}

          {can?.selecionarAnoLectivo && (
            <Select
              value={normalizeFilterValue(anoLectivoId)}
              onValueChange={onAnoLectivoChange}
            >
              <SelectTrigger className="w-full sm:w-44">
                <SelectValue placeholder="Ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={String(ano.id)}>
                    {ano.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          )}

          <div className="w-full sm:w-70">
            <TableSearch
              bare
              value={search}
              onChange={onSearchChange}
              onSubmit={onSearchSubmit}
              placeholder="Pesquisar grupo PAP..."
            />
          </div>
        </div>
      </div>
    </Card>
  );
}
