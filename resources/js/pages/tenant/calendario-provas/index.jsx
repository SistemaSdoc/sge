import { Head, Link, router } from '@inertiajs/react';
import {
    create,
    edit,
    index,
    show,
} from '@/actions/App/Http/Controllers/Tenant/CalendarioProvaController';

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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { CalendarIcon, ChevronDownIcon } from 'lucide-react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import TablePagination from '@/components/table-pagination';
import { EmptyState } from '@/components/empty-state';

const TIPO_BADGE = {
    trimestral: { label: 'Trimestral', className: 'bg-blue-100 text-blue-700' },
    recurso: { label: 'Recurso', className: 'bg-orange-100 text-orange-700' },
    exame_especial: { label: 'Exame especial', className: 'bg-purple-100 text-purple-700' },
};

const TIPO_OPTIONS = [
    { value: '', label: 'Todos os tipos' },
    { value: 'trimestral', label: 'Trimestral' },
    { value: 'recurso', label: 'Recurso' },
    { value: 'exame_especial', label: 'Exame especial' },
];

export default function CalendarioProvasIndex({ calendarios, anosLectivos, anoLectivoId, filters, can, anoLectivoActivo }) {
    function applyFilter(key, value) {
        router.get(
            index().url,
            { ...filters, [key]: value },
            { preserveState: true, replace: true },
        );
    }

    function handlePageChange(page) {
        router.visit(index().url, {
            data: {
                ...Object.fromEntries(new URLSearchParams(window.location.search)),
                page,
            },
            preserveScroll: true,
        });
    }

    const anoActualNome = anosLectivos.find((a) => a.id === anoLectivoId)?.nome ?? 'seleccionado';
    const tipoActual = TIPO_OPTIONS.find((o) => o.value === (filters.tipo ?? ''))?.label ?? 'Todos os tipos';
    const isEmpty = calendarios.data.length === 0;

    return (
        <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
            <Head title="Calendários de Provas" />

            <Card className="gap-0">
                <CardHeader className="border-b">
                    <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                        <div>
                            <CardTitle>Calendários de provas</CardTitle>
                            <CardDescription>
                                Gerencie os calendários por turno e tipo de prova
                            </CardDescription>
                        </div>
                        {can?.create && (
                            <CardAction className="w-full sm:w-auto">
                                <Button asChild className="w-full hover:cursor-pointer sm:w-auto">
                                    <Link href={create().url}>
                                        Novo calendário
                                    </Link>
                                </Button>
                            </CardAction>
                        )}
                    </div>
                </CardHeader>

                <CardContent className="p-0!">
                    {/* Barra de filtros */}
                    <div className="flex justify-between border-b bg-muted/30 px-4 py-3">

                        <span className="hidden items-center text-sm text-muted-foreground md:flex">
                            {calendarios.total} registo{calendarios.total !== 1 ? 's' : ''}
                        </span>
                        <div className="flex w-full flex-col gap-2 md:w-auto md:flex-row md:gap-2">

                            {/* Filtro Tipo */}
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="w-full hover:cursor-pointer md:w-auto"
                                    >
                                        {tipoActual}
                                        <ChevronDownIcon aria-hidden="true" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" className="md:w-48">
                                    <DropdownMenuLabel>Tipo de prova</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {TIPO_OPTIONS.map((o) => (
                                        <DropdownMenuItem
                                            key={o.value}
                                            onClick={() => applyFilter('tipo', o.value)}
                                            className={`${(filters.tipo ?? '') === o.value ? 'bg-muted' : ''} hover:cursor-pointer`}
                                        >
                                            {o.label}
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>

                            {/* Filtro Ano Lectivo */}
                            {can?.create && (
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button
                                        variant="outline"
                                        className="w-full hover:cursor-pointer md:w-auto"
                                    >
                                        {anoActualNome}
                                        <ChevronDownIcon aria-hidden="true" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="start" className="md:w-48">
                                    <DropdownMenuLabel>Ano Lectivo</DropdownMenuLabel>
                                    <DropdownMenuSeparator />
                                    {anosLectivos.map((a) => (
                                        <DropdownMenuItem
                                            key={a.id}
                                            onClick={() => applyFilter('ano_lectivo_id', a.id)}
                                            className={`${anoLectivoId === a.id ? 'bg-muted' : ''} hover:cursor-pointer`}
                                        >
                                            {a.nome}
                                        </DropdownMenuItem>
                                    ))}
                                </DropdownMenuContent>
                            </DropdownMenu>
                            )}
                        </div>

                    </div>

                    {/* Tabela ou empty state */}
                    {isEmpty ? (
                        <EmptyState
                            variant="table"
                            icon={CalendarIcon}
                            title="Nenhum calendário encontrado"
                            description="Ainda não existem calendários de provas para os filtros seleccionados."
                        />
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/72">
                                    <TableHead className="px-4">Curso / Classe / Turno</TableHead>
                                    <TableHead className="px-4">Tipo</TableHead>
                                    <TableHead className="px-4">Trimestre</TableHead>
                                    {/* <TableHead className="px-4">Ano lectivo</TableHead> */}
                                    <TableHead className="px-4">Estado</TableHead>
                                    <TableHead className="px-4">Disciplinas</TableHead>
                                    {calendarios.data.some(cal => cal.can?.view || cal.can?.update) && (
                                        <TableHead className="px-4 text-right">Ações</TableHead>
                                    )}
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {calendarios.data.map((cal) => {
                                    const cct = cal.curso_classe_turno;
                                    const curso = cct?.curso_classe?.curso_tutelado?.instituicao_curso?.curso?.nome ?? '—';
                                    const classe = cct?.curso_classe?.classe?.nome ?? '—';
                                    const turno = cct?.turno?.nome ?? '—';
                                    const badge = TIPO_BADGE[cal.tipo] ?? { label: cal.tipo, className: '' };

                                    return (
                                        <TableRow key={cal.id}>
                                            <TableCell className="px-4">
                                                <p className="text-sm font-medium">{curso}</p>
                                                <p className="text-xs text-muted-foreground">
                                                    {classe} · Turno {turno}
                                                </p>
                                            </TableCell>
                                            <TableCell className="px-4">
                                                <span className={`inline-flex items-center px-2.5 py-0.5 text-xs font-medium ${badge.className}`}>
                                                    {badge.label}
                                                </span>
                                            </TableCell>
                                            <TableCell className="px-4 text-sm">
                                                {cal.tipo === 'trimestral' && cal.trimestre
                                                    ? `${cal.trimestre}º Trimestre`
                                                    : '—'}
                                            </TableCell>
                                            {/* <TableCell className="px-4 text-sm">
                                                {cal.ano_lectivo?.nome}
                                            </TableCell> */}
                                            <TableCell className="px-4">
                                                {cal.ativo
                                                    ? <Badge className="bg-green-100 text-green-700">Publicado</Badge>
                                                    : <Badge variant="outline">Rascunho</Badge>
                                                }
                                            </TableCell>
                                            <TableCell className="px-4 text-sm text-muted-foreground">
                                                {cal.entradas_count ?? cal.entradas?.length ?? 0} disciplinas
                                            </TableCell>
                                            {(cal.can?.view || cal.can?.update) && (
                                                <TableCell className="px-4 text-right">
                                                    <div className="flex items-center justify-end gap-1">
                                                        {cal.can?.view && (
                                                            <Button size="xs" variant="outline" asChild className="hover:cursor-pointer">
                                                                <Link href={show(cal.id).url}>
                                                                    Ver detalhes
                                                                </Link>
                                                            </Button>
                                                        )}
                                                        {cal.can?.update && (
                                                            <Button size="xs" variant="outline" asChild className="ml-2 hover:cursor-pointer">
                                                                <Link href={edit(cal.id).url}>
                                                                    Editar
                                                                </Link>
                                                            </Button>
                                                        )}
                                                    </div>
                                                </TableCell>
                                            )}
                                        </TableRow>
                                    );
                                })}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>

                <TablePagination
                    pagination={calendarios}
                    onPageChange={handlePageChange}
                />
            </Card>
        </div>
    );
}