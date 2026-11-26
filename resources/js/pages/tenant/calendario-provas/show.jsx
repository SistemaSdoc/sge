import { Head, Link, router } from '@inertiajs/react';
import { ArrowLeftIcon, Pencil, Trash2, Eye, EyeOff } from 'lucide-react';
import {
    destroy as destroyCalendario,
    edit,
    index,
    toggleAtivo as toggleAtivoCalendario,
} from '@/actions/App/Http/Controllers/Tenant/CalendarioProvaController';
import { format, parseISO } from 'date-fns';

import { Badge } from '@/components/ui/badge';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import { useDialog } from '@/hooks/use-dialog';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const TIPO_BADGE = {
    trimestral:     { label: 'Trimestral',    className: 'bg-blue-100 text-blue-700' },
    recurso:        { label: 'Recurso',        className: 'bg-orange-100 text-orange-700' },
    exame_especial: { label: 'Exame especial', className: 'bg-purple-100 text-purple-700' },
};

const DIA_SEMANA = ['Dom', '2ª Feira', '3ª Feira', '4ª Feira', '5ª Feira', '6ª Feira', 'Sáb'];

export default function CalendarioProvasShow({ calendario, can }) {
    const { deleteConfirm } = useDialog();
    const cct    = calendario.curso_classe_turno;
    const curso  = cct?.curso_classe?.curso_tutelado?.instituicao_curso?.curso?.nome ?? '—';
    const classe = cct?.curso_classe?.classe?.nome ?? '—';
    const turno  = cct?.turno?.nome ?? '—';
    const badge  = TIPO_BADGE[calendario.tipo] ?? { label: calendario.tipo, className: '' };

    const entradas = calendario.entradas ?? [];

    // Derivados para os stat cards
    const numDisciplinas = entradas.length;
    const datas          = [...new Set(entradas.map((e) => e.data))].sort();
    const numDias        = datas.length;
    const periodo        = datas.length >= 2
        ? `${formatData(datas[0])} – ${formatData(datas[datas.length - 1])}`
        : datas.length === 1 ? formatData(datas[0]) : '—';

    // Grelha visual
    const horarios        = [...new Set(entradas.map((e) => `${e.hora_inicio}–${e.hora_fim}`))].sort();
    const datasOrdenadas  = datas.map((d) => parseISO(d));

    const lookup = {};
    entradas.forEach((e) => {
        lookup[`${e.hora_inicio}–${e.hora_fim}|${e.data}`] = e.disciplina?.nome ?? e.disciplina?.sigla ?? '?';
    });

    function toggleAtivo() {
        router.patch(toggleAtivoCalendario(calendario.id).url);
    }

    function destroy() {
        deleteConfirm({
            title: 'Tens a certeza?',
            description: 'Esta acção é irreversível. O calendário de provas será removido permanentemente.',
            confirmLabel: 'Eliminar',
            confirmFn: () => router.delete(destroyCalendario(calendario.id).url),
        });
    }

    return (
        <div className="mx-auto w-full max-w-7xl p-4 md:p-6">
            <Head title={`${curso} — ${classe}`} />

            <div className="space-y-6">
                <Card className="gap-0! overflow-visible pb-0">
                    <CardHeader className="border-b border-foreground/10">
                        <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                            <div className="min-w-0 space-y-1">
                                <Breadcrumb>
                                    <BreadcrumbList>
                                        <BreadcrumbItem>
                                            <BreadcrumbLink asChild>
                                                <Link
                                                    className="text-sm font-semibold text-primary hover:text-primary/80"
                                                    href={index().url}
                                                >
                                                    Calendários de provas
                                                </Link>
                                            </BreadcrumbLink>
                                        </BreadcrumbItem>
                                        <BreadcrumbSeparator />
                                        <BreadcrumbItem>
                                            <BreadcrumbPage className="text-sm font-semibold text-secondary">
                                                Ver calendário
                                            </BreadcrumbPage>
                                        </BreadcrumbItem>
                                    </BreadcrumbList>
                                </Breadcrumb>

                                <CardTitle className="text-xl">
                                    {curso} — {classe} · Turno {turno}
                                </CardTitle>
                                <CardDescription>
                                    Ano lectivo {calendario.ano_lectivo?.nome}
                                </CardDescription>
                                <div className="flex flex-wrap gap-2 pt-1">
                                    <span className={`inline-flex items-center px-2.5 py-0.5 text-xs font-medium ${badge.className}`}>
                                        {badge.label}
                                        {calendario.tipo === 'trimestral' && calendario.trimestre
                                            ? ` · ${calendario.trimestre}º Trimestre`
                                            : ''}
                                    </span>
                                    {calendario.ativo
                                        ? <Badge className="bg-green-100 text-green-700">Publicado</Badge>
                                        : <Badge variant="outline">Rascunho</Badge>
                                    }
                                </div>
                            </div>

                            <div className="flex shrink-0 flex-col gap-2 sm:flex-row">
                                <Button asChild variant="outline" className="w-full justify-center hover:cursor-pointer sm:w-auto">
                                    <Link href={index().url}>
                                        <ArrowLeftIcon data-icon="inline-start" />
                                        Voltar
                                    </Link>
                                </Button>
                                {can?.toggleAtivo && (
                                <Button variant="outline" onClick={toggleAtivo} className="w-full justify-center hover:cursor-pointer sm:w-auto">
                                    {calendario.ativo
                                        ? <><EyeOff className="mr-2 h-4 w-4" />Rascunho</>
                                        : <><Eye className="mr-2 h-4 w-4" />Publicar</>
                                    }
                                </Button>
                                )}
                                {can?.update && (
                                <Button variant="outline" asChild className="w-full justify-center hover:cursor-pointer sm:w-auto">
                                    <Link href={edit(calendario.id).url}>
                                        Editar
                                    </Link>
                                </Button>
                                )}
                                {can?.delete && (
                                <Button variant="destructive" size="icon" onClick={destroy} className="hover:cursor-pointer">
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                                )}
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                {/* Stat cards */}
                <div className="grid grid-cols-3 gap-4">
                    <StatCard label="Disciplinas"  value={numDisciplinas} />
                    <StatCard label="Dias de prova" value={numDias} />
                    <StatCard label="Período"       value={periodo} />
                </div>

                {/* Grelha visual */}
               {/* {datas.length > 0 && (
                    <Card className="gap-0 overflow-x-auto">
                        <CardHeader className="border-b">
                            <div className="flex items-center justify-between">
                                <CardTitle className="text-sm font-medium">Grelha de provas</CardTitle>
                                <span className="text-xs text-muted-foreground">
                                    {horarios.join(' · ')}
                                </span>
                            </div>
                        </CardHeader>
                        <CardContent className="p-0!">
                            <Table>
                                <TableHeader>
                                    <TableRow className="bg-muted/72">
                                        <TableHead className="w-32 px-4">Horário</TableHead>
                                        {datasOrdenadas.map((d, idx) => (
                                            <TableHead key={idx} className="min-w-[80px] px-4 text-center">
                                                <div className="text-xs font-medium">{DIA_SEMANA[d.getDay()]}</div>
                                                <div className="text-xs text-muted-foreground">{format(d, 'dd/MM')}</div>
                                            </TableHead>
                                        ))}
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {horarios.map((horario) => (
                                        <TableRow key={horario}>
                                            <TableCell className="whitespace-nowrap px-4 text-xs font-medium text-muted-foreground">
                                                {horario.replace('–', ' – ')}
                                            </TableCell>
                                            {datas.map((data) => {
                                                const cell = lookup[`${horario}|${data}`];
                                                return (
                                                    <TableCell key={data} className="p-1 text-center">
                                                        {cell
                                                            ? <span className="inline-block rounded bg-blue-50 px-2 py-1 text-xs leading-tight text-blue-700">{cell}</span>
                                                            : <span className="text-xs text-muted-foreground">—</span>
                                                        }
                                                    </TableCell>
                                                );
                                            })}
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )} */}

                {/* Lista tabular */}
                <Card className="gap-0 pb-0">
                    <CardHeader className="border-b">
                        <CardTitle className="text-sm font-medium">Lista de provas marcadas</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0!">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/72">
                                    <TableHead className="px-4">Disciplina</TableHead>
                                    <TableHead className="px-4">Data</TableHead>
                                    <TableHead className="px-4">Hora início</TableHead>
                                    <TableHead className="px-4">Hora fim</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {entradas.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={4} className="py-10 text-center text-muted-foreground">
                                            Nenhuma entrada registada.
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    entradas.map((e, i) => (
                                        <TableRow key={i}>
                                            <TableCell className="px-4 font-medium">{e.disciplina?.nome ?? '—'}</TableCell>
                                            <TableCell className="px-4">{formatData(e.data)}</TableCell>
                                            <TableCell className="px-4">{e.hora_inicio}</TableCell>
                                            <TableCell className="px-4">{e.hora_fim}</TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </div>
    );
}

function StatCard({ label, value }) {
    return (
        <div className=" border bg-muted/30 p-4 space-y-1">
            <p className="text-xs text-muted-foreground">{label}</p>
            <p className="text-2xl font-semibold">{value}</p>
        </div>
    );
}

function formatData(dateStr) {
    if (!dateStr) return '—';
    try {
        return format(parseISO(dateStr), 'dd/MM/yyyy');
    } catch {
        return dateStr;
    }
}