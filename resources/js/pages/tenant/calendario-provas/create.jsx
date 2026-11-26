import { Head, Link, useForm } from '@inertiajs/react';
import { Plus, Trash2, Calendar, RefreshCw, Star } from 'lucide-react';
import {
    index,
    store,
    update,
} from '@/actions/App/Http/Controllers/Tenant/CalendarioProvaController';

import InputError from '@/components/input-error';
import {
    Breadcrumb,
    BreadcrumbItem,
    BreadcrumbLink,
    BreadcrumbList,
    BreadcrumbPage,
    BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Field,
    FieldError,
    FieldGroup,
    FieldLabel,
    FieldSet,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectGroup,
    SelectItem,
    SelectLabel,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

const TIPOS = [
    { value: 'trimestral', label: 'Trimestral', icon: Calendar },
    { value: 'recurso', label: 'Recurso', icon: RefreshCw },
    { value: 'exame_especial', label: 'Exame especial', icon: Star },
];

export default function CalendarioProvasForm({ cursos = [], anosLectivos = [], anoLectivoActivo, calendario }) {
    const isEditing = Boolean(calendario);
    const titulo = isEditing ? 'Editar calendário de provas' : 'Criar calendário de provas';
    const cursoInicialId = calendario?.curso_classe_turno?.curso_classe?.curso_tutelado?.instituicao_curso?.curso?.id ?? '';
    const classeInicialId = calendario?.curso_classe_turno?.curso_classe?.classe?.id ?? '';

    const { data, setData, post, put, processing, errors } = useForm({
        ano_lectivo_id: calendario?.ano_lectivo_id ?? anoLectivoActivo?.id ?? '',
        curso_id: cursoInicialId ? String(cursoInicialId) : '',
        classe_id: classeInicialId ? String(classeInicialId) : '',
        curso_classe_turno_id: calendario?.curso_classe_turno_id ?? '',
        tipo: calendario?.tipo ?? 'trimestral',
        trimestre: calendario?.trimestre ? String(calendario.trimestre) : '',
        entradas: calendario?.entradas?.length
            ? calendario.entradas.map((e) => ({
                disciplina_id: e.disciplina_id,
                data: String(e.data ?? '').slice(0, 10),
                hora_inicio: String(e.hora_inicio ?? '').slice(0, 5),
                hora_fim: String(e.hora_fim ?? '').slice(0, 5),
            }))
            : [{ disciplina_id: '', data: '', hora_inicio: '', hora_fim: '' }],
    });

    // ── Derivados ────────────────────────────────────────────────
    const cursoSelecionado = cursos.find((c) => String(c.id) === String(data.curso_id));
    const classes = cursoSelecionado?.classes ?? [];
    const classeSelecionada = classes.find((cl) => String(cl.id) === String(data.classe_id));
    const turnos = classeSelecionada?.turnos ?? [];
    const turnoSelecionado = turnos.find((t) => String(t.id) === String(data.curso_classe_turno_id));

    const disciplinasDisponiveis = (turnoSelecionado?.classe_turno_disciplinas ?? [])
        .filter((item) => String(item.ano_lectivo_id) === String(data.ano_lectivo_id));

    // ── Handlers ─────────────────────────────────────────────────
    function clearDisciplinas() {
        setData('entradas', data.entradas.map((e) => ({ ...e, disciplina_id: '' })));
    }

    function changeCurso(value) {
        setData((prev) => ({ ...prev, curso_id: value, classe_id: '', curso_classe_turno_id: '' }));
        clearDisciplinas();
    }

    function changeClasse(value) {
        setData((prev) => ({ ...prev, classe_id: value, curso_classe_turno_id: '' }));
        clearDisciplinas();
    }

    function changeTurno(value) {
        setData((prev) => ({ ...prev, curso_classe_turno_id: value }));
        clearDisciplinas();
    }

    function changeAnoLectivo(value) {
        setData('ano_lectivo_id', value);
        clearDisciplinas();
    }

    function addEntrada() {
        setData('entradas', [
            ...data.entradas,
            { disciplina_id: '', data: '', hora_inicio: '', hora_fim: '' },
        ]);
    }

    function removeEntrada(i) {
        setData('entradas', data.entradas.filter((_, idx) => idx !== i));
    }

    function updateEntrada(i, field, value) {
        setData('entradas', data.entradas.map((e, idx) => idx === i ? { ...e, [field]: value } : e));
    }

    function submit(e) {
        e.preventDefault();
        if (isEditing) {
            put(update(calendario.id).url);
            return;
        }
        post(store().url);
    }

    const podeSubmeter = Boolean(data.curso_classe_turno_id && data.ano_lectivo_id);

    return (
        <div className="mx-auto w-full max-w-3xl p-4 md:p-6">
            <Head title={titulo} />

            {/* Header card */}
            <Card className="mb-6 gap-0! overflow-visible pb-0">
                <CardHeader className="border-b border-foreground/10">
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
                                    {isEditing ? 'Editar calendário de provas' : 'Criar calendário de provas'}
                                </BreadcrumbPage>
                            </BreadcrumbItem>
                        </BreadcrumbList>
                    </Breadcrumb>
                  
                </CardHeader>
            </Card>

            <form onSubmit={submit} className="space-y-6">
                {/* Configuração geral */}
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>
                            Configuração geral
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="pt-4">
                        <FieldGroup>
                            <FieldSet>
                                {/* Ano lectivo */}
                                <Field data-invalid={!!errors.ano_lectivo_id}>
                                    <FieldLabel>Ano lectivo</FieldLabel>
                                    <Select value={data.ano_lectivo_id} onValueChange={changeAnoLectivo}>
                                        <SelectTrigger>
                                            <SelectValue placeholder="Seleccionar ano lectivo" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectGroup>
                                                {anosLectivos.map((a) => (
                                                    <SelectItem key={a.id} value={a.id}>{a.nome}</SelectItem>
                                                ))}
                                            </SelectGroup>
                                        </SelectContent>
                                    </Select>
                                    {errors.ano_lectivo_id && <FieldError>{errors.ano_lectivo_id}</FieldError>}
                                </Field>

                                {/* Curso / Classe / Turno — 3 selects separados */}
                                <div className="grid grid-cols-1 gap-4 md:grid-cols-3">
                                    <Field data-invalid={!!errors.curso_id}>
                                        <FieldLabel>Curso</FieldLabel>
                                        <Select value={data.curso_id} onValueChange={changeCurso}>
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar curso" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    {cursos.map((c) => (
                                                        <SelectItem key={c.id} value={String(c.id)}>{c.nome}</SelectItem>
                                                    ))}
                                                    {!cursos.length && (
                                                        <p className="px-2 py-1.5 text-sm text-muted-foreground">
                                                            Nenhum curso disponível.
                                                        </p>
                                                    )}
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </Field>

                                    <Field data-invalid={!!errors.classe_id}>
                                        <FieldLabel>Classe</FieldLabel>
                                        <Select
                                            value={data.classe_id}
                                            onValueChange={changeClasse}
                                            disabled={!data.curso_id}
                                        >
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder={
                                                        !data.curso_id
                                                            ? 'Seleccione um curso primeiro'
                                                            : 'Seleccionar classe'
                                                    }
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Classes</SelectLabel>
                                                    {classes.map((cl) => (
                                                        <SelectItem key={cl.id} value={String(cl.id)}>{cl.nome}</SelectItem>
                                                    ))}
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                    </Field>

                                    <Field data-invalid={!!errors.curso_classe_turno_id}>
                                        <FieldLabel>Turno</FieldLabel>
                                        <Select
                                            value={data.curso_classe_turno_id}
                                            onValueChange={changeTurno}
                                            disabled={!data.classe_id}
                                        >
                                            <SelectTrigger>
                                                <SelectValue
                                                    placeholder={
                                                        !data.classe_id
                                                            ? 'Seleccione uma classe primeiro'
                                                            : !turnos.length
                                                                ? 'Nenhum turno disponível'
                                                                : 'Seleccionar turno'
                                                    }
                                                />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectGroup>
                                                    <SelectLabel>Turnos</SelectLabel>
                                                    {turnos.map((t) => (
                                                        <SelectItem key={t.id} value={String(t.id)}>{t.nome}</SelectItem>
                                                    ))}
                                                </SelectGroup>
                                            </SelectContent>
                                        </Select>
                                        {errors.curso_classe_turno_id && <FieldError>{errors.curso_classe_turno_id}</FieldError>}
                                    </Field>
                                </div>

                                <div className="grid grid-cols-1 gap-4 md:grid-cols-2">
                                    {/* Tipo de prova */}
                                    <Field data-invalid={!!errors.tipo}>
                                        <FieldLabel>Tipo de prova</FieldLabel>
                                        <Select
                                            value={data.tipo}
                                            onValueChange={(tipo) => setData((prev) => ({
                                                ...prev,
                                                tipo,
                                                trimestre: tipo === 'trimestral' ? prev.trimestre : '',
                                            }))}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Seleccionar tipo" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="trimestral">Trimestral</SelectItem>
                                                <SelectItem value="recurso">Recurso</SelectItem>
                                                <SelectItem value="exame_especial">Exame especial</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.tipo && <FieldError>{errors.tipo}</FieldError>}
                                    </Field>

                                    {/* Trimestre */}
                                    {data.tipo === 'trimestral' && (
                                        <Field data-invalid={!!errors.trimestre}>
                                            <FieldLabel>Trimestre</FieldLabel>
                                            <Select
                                                value={data.trimestre}
                                                onValueChange={(v) => setData('trimestre', v)}
                                            >
                                                <SelectTrigger className="w-40">
                                                    <SelectValue placeholder="Seleccionar" />
                                                </SelectTrigger>
                                                <SelectContent>
                                                    <SelectItem value="1">1º Trimestre</SelectItem>
                                                    <SelectItem value="2">2º Trimestre</SelectItem>
                                                    <SelectItem value="3">3º Trimestre</SelectItem>
                                                </SelectContent>
                                            </Select>
                                            {errors.trimestre && <FieldError>{errors.trimestre}</FieldError>}
                                        </Field>
                                    )}
                                </div>
                            </FieldSet>
                        </FieldGroup>
                    </CardContent>
                </Card>

                {/* Entradas do calendário */}
                <Card>
                    <CardHeader className="border-b">
                        <CardTitle>
                            Entradas do calendário
                        </CardTitle>
                    </CardHeader>

                    <CardContent className="space-y-4 pt-4">
                        {/* Header de colunas */}
                        <div className="grid grid-cols-[1fr_140px_100px_100px_32px] gap-2 px-1 text-xs text-muted-foreground">
                            <span>Disciplina</span>
                            <span>Data</span>
                            <span>Hora início</span>
                            <span>Hora fim</span>
                            <span />
                        </div>

                        {data.entradas.map((entrada, i) => (
                            <div
                                key={i}
                                className="grid grid-cols-[1fr_140px_100px_100px_32px] items-center gap-2"
                            >
                                <Select
                                    value={entrada.disciplina_id}
                                    onValueChange={(v) => updateEntrada(i, 'disciplina_id', v)}
                                    disabled={!data.curso_classe_turno_id}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder={
                                            !data.curso_classe_turno_id
                                                ? 'Seleccione um turno primeiro'
                                                : 'Disciplina'
                                        } />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {disciplinasDisponiveis.map((item) => {
                                            const disciplina = item.disciplina;
                                            return disciplina ? (
                                                <SelectItem key={item.id} value={String(disciplina.id)}>
                                                    {disciplina.nome ?? disciplina.sigla}
                                                </SelectItem>
                                            ) : null;
                                        })}
                                    </SelectContent>
                                </Select>

                                <Input
                                    type="date"
                                    value={entrada.data}
                                    onChange={(e) => updateEntrada(i, 'data', e.target.value)}
                                />
                                <Input
                                    type="time"
                                    value={entrada.hora_inicio}
                                    onChange={(e) => updateEntrada(i, 'hora_inicio', e.target.value)}
                                />
                                <Input
                                    type="time"
                                    value={entrada.hora_fim}
                                    onChange={(e) => updateEntrada(i, 'hora_fim', e.target.value)}
                                />

                                <button
                                    type="button"
                                    onClick={() => removeEntrada(i)}
                                    disabled={data.entradas.length === 1}
                                    className="text-muted-foreground transition-colors hover:cursor-pointer hover:text-destructive disabled:opacity-30"
                                >
                                    <Trash2 className="h-4 w-4" />
                                </button>
                            </div>
                        ))}

                        <Button
                            type="button"
                            variant="outline"
                            className="w-full hover:cursor-pointer"
                            onClick={addEntrada}
                            disabled={!data.curso_classe_turno_id}
                        >
                            <Plus className="mr-2 h-4 w-4" />
                            Adicionar disciplina
                        </Button>

                        <p className="text-xs text-muted-foreground">
                            As disciplinas disponíveis são as do turno seleccionado.
                        </p>
                    </CardContent>
                </Card>

                {/* Acções */}
                <div className="flex justify-end gap-3">
                    <Button variant="outline" asChild className="hover:cursor-pointer">
                        <Link href={index().url}>Cancelar</Link>
                    </Button>
                    <Button
                        type="submit"
                        disabled={processing || !podeSubmeter}
                        className="hover:cursor-pointer"
                    >
                        {processing
                            ? (isEditing ? 'A actualizar...' : 'A guardar...')
                            : (isEditing ? 'Actualizar calendário' : 'Guardar calendário')}
                    </Button>
                </div>
            </form>
        </div>
    );
}