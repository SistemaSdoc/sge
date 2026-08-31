import { router } from '@inertiajs/react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Minus } from 'lucide-react';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Button } from '@/components/ui/button';

import { createIndependente } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';

const normalizeFilterValue = (value) =>
  value === null || value === undefined || value === '' ? '' : String(value);

export function Header({
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
  onAddGrupo
}) {
  const instituicaoSeleccionada =
    instituicoes.find((item) => normalizeFilterValue(item.id) === normalizeFilterValue(filtroInstituicao)) ?? instituicao;

  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <CardTitle className="min-w-0 space-y-1">
          <Breadcrumb>
            <BreadcrumbList>
              <BreadcrumbItem className="min-w-0 shrink">
                <BreadcrumbLink asChild>
                  <span className="line-clamp-1 truncate text-sm font-semibold text-primary">
                    {instituicaoSeleccionada.nome}
                  </span>
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem className="min-w-0 shrink">
                <BreadcrumbPage className="line-clamp-1 truncate text-sm font-semibold text-secondary">
                  Grupos para Prova de Aptidão Profissional
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>
        </CardTitle>

        <CardDescription>
          Grupos criados para a Prova de Aptidão Profissional (PAP) da
          instituição{' '}
          <span className="font-bold">{instituicaoSeleccionada.nome}</span>
        </CardDescription>

        <CardAction>
          <CardAction>
            <Button size="sm" className="w-full sm:w-auto" onClick={onAddGrupo}>
              Adicionar grupo
            </Button>
          </CardAction>
        </CardAction>
      </CardHeader>

      {/* Filtros */}
      <div className="flex flex-col gap-3 overflow-hidden px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
        <h1 className="text-sm font-semibold whitespace-nowrap">Filtros</h1>

        <div className="flex w-full flex-col justify-end gap-2 sm:w-auto sm:flex-row">
          <Select
            value={normalizeFilterValue(filtroInstituicao)}
            onValueChange={onInstituicaoChange}
          >
            <SelectTrigger className="w-full sm:w-56">
              <SelectValue placeholder="Instituição" />
            </SelectTrigger>
            <SelectContent>
              {instituicoes.map((item) => (
                <SelectItem key={item.id} value={String(item.id)}>
                  {item.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

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
        </div>
      </div>

      {/* Cursos tutelados */}
      <div className="overflow-x-auto border-t border-foreground/10 sm:overflow-visible">
        {cursosTutelados.length > 0 ? (
          <div
            className="-mb-px grid"
            style={{
              gridTemplateColumns: 'repeat(auto-fill, minmax(200px, 1fr))',
            }}
          >
            {cursosTutelados.map((curso, index) => {
              const isLastItem = index === cursosTutelados.length - 1;
              const cursoValue = normalizeFilterValue(curso.id);
              const isSelected = normalizeFilterValue(filtroCurso) === cursoValue;

              return (
                <button
                  type="button"
                  key={curso.id}
                  onClick={() => onCursoChange(curso.id)}
                  aria-pressed={isSelected}
                  className="text-left"
                  style={isAloneInRow ? { gridColumn: '1 / -1' } : {}}
                >
                  <div
                    className={`h-full cursor-pointer border-b border-foreground/10 px-3 py-3 text-card-foreground transition-colors hover:bg-accent hover:text-secondary active:bg-accent sm:px-4 sm:py-4 ${
                      isSelected ? 'bg-accent text-secondary' : 'bg-card'
                    } ${!isLastItem ? 'border-r' : ''}`}
                  >
                    <h3 className="mb-0.5 text-xs font-medium sm:mb-1 sm:text-sm">
                      {curso.nome}
                    </h3>
                    <p className="text-xs text-muted-foreground">
                      {isSelected
                        ? 'A visualizar os grupos deste curso'
                        : 'Clique para ver os grupos deste curso'}
                    </p>
                  </div>
                </button>
              );
            })}
          </div>
        ) : (
          <div className="flex flex-col items-center justify-center bg-card p-6 text-center">
            <p className="text-xs text-muted-foreground">
              Nenhum curso disponível para a instituição selecionada.
            </p>
          </div>
        )}
      </div>
    </Card>
  );
}
