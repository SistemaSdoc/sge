import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Card, CardDescription, CardHeader } from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

const valorFiltro = (value) => String(value ?? '');

export function PautasHeader({
  instituicao,
  instituicoes = [],
  cursos = [],
  anosLectivos = [],
  filtros = {},
  onInstituicaoChange,
  onCursoChange,
  onAnoLectivoChange,
}) {
  const instituicaoSeleccionada =
    instituicoes.find(
      (item) => valorFiltro(item.id) === valorFiltro(filtros.instituicao_id),
    ) ?? instituicao;

  return (
    <Card className="gap-0! overflow-visible pb-0">
      <CardHeader className="border-b border-foreground/10">
        <div className="min-w-0 space-y-1">
          <Breadcrumb>
            <BreadcrumbList className="flex-wrap">
              <BreadcrumbItem className="min-w-0">
                <BreadcrumbLink asChild>
                  <span className="line-clamp-2 text-sm font-semibold text-primary">
                    {instituicaoSeleccionada?.nome}
                  </span>
                </BreadcrumbLink>
              </BreadcrumbItem>
              <BreadcrumbSeparator />
              <BreadcrumbItem className="min-w-0">
                <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                  Pautas
                </BreadcrumbPage>
              </BreadcrumbItem>
            </BreadcrumbList>
          </Breadcrumb>

          <CardDescription>
            Seleccione uma instituição, curso e ano lectivo para consultar as
            pautas
          </CardDescription>
        </div>
      </CardHeader>

      <div className="flex flex-col gap-3 overflow-hidden px-4 py-3 sm:flex-row sm:items-center sm:justify-between sm:gap-0">
        <h2 className="text-sm font-semibold whitespace-nowrap">Filtros</h2>

        <div className="flex w-full flex-col justify-end gap-2 sm:w-auto sm:flex-row">
          <Select
            value={valorFiltro(filtros.instituicao_id)}
            onValueChange={onInstituicaoChange}
          >
            <SelectTrigger className="w-full sm:w-56">
              <SelectValue placeholder="Instituição" />
            </SelectTrigger>
            <SelectContent>
              {instituicoes.map((item) => (
                <SelectItem key={item.id} value={valorFiltro(item.id)}>
                  {item.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select
            value={valorFiltro(filtros.curso_tutelado_id) || 'todos'}
            onValueChange={onCursoChange}
          >
            <SelectTrigger className="w-full sm:w-56">
              <SelectValue placeholder="Todos os cursos" />
            </SelectTrigger>
            <SelectContent>
              <SelectItem value="todos">Todos os cursos</SelectItem>
              {cursos.map((curso) => (
                <SelectItem key={curso.id} value={valorFiltro(curso.id)}>
                  {curso.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>

          <Select
            value={valorFiltro(filtros.ano_lectivo_id)}
            onValueChange={onAnoLectivoChange}
          >
            <SelectTrigger className="w-full sm:w-44">
              <SelectValue placeholder="Ano lectivo" />
            </SelectTrigger>
            <SelectContent>
              {anosLectivos.map((ano) => (
                <SelectItem key={ano.id} value={valorFiltro(ano.id)}>
                  {ano.nome}
                </SelectItem>
              ))}
            </SelectContent>
          </Select>
        </div>
      </div>
    </Card>
  );
}
