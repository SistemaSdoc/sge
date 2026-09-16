import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
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
import MultipleSelect from '@/components/multiple-select';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectLabel,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ArrowUpLeft } from 'lucide-react';
import { useEffect, useState } from 'react';
import {
  cursosDisponiveis as cursosDisponiveisUrl,
  cursoDetalhes as cursoDetalhesUrl,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export function CursoForm({
  title,
  instituicao,
  classes,
  cursos,
  niveisEnsino,
  tenantsTutores,
  data,
  setData,
  errors,
  processing,
  onSubmit,
}) {
  const [cursosDisponiveis, setCursosDisponiveis] = useState(cursos ?? []);
  const [carregandoCursos, setCarregandoCursos] = useState(false);

  useEffect(() => {
    if (instituicao.tipo !== 'colegio' || !data.tenant_tutor_id) {
      setCursosDisponiveis(cursos ?? []);
      return;
    }

    const controller = new AbortController();
    setCarregandoCursos(true);
    fetch(
      `${cursosDisponiveisUrl({ instituicao: instituicao.id }).url}?tenant_tutor_id=${encodeURIComponent(data.tenant_tutor_id)}`,
      {
        headers: { Accept: 'application/json' },
        signal: controller.signal,
      },
    )
      .then((response) => {
        if (!response.ok) {
          throw new Error('Não foi possível carregar os cursos do instituto.');
        }

        return response.json();
      })
      .then((payload) => {
        if (controller.signal.aborted) {
          return;
        }

        const cursosDoTutor = payload.data ?? [];
        setCursosDisponiveis(cursosDoTutor);

        if (!cursosDoTutor.some((curso) => curso.id === data.curso_id)) {
          setData('curso_id', '');
        }
      })
      .catch((error) => {
        if (error.name === 'AbortError') {
          return;
        }

        setCursosDisponiveis([]);
        setData('curso_id', '');
      })
      .finally(() => {
        if (!controller.signal.aborted) {
          setCarregandoCursos(false);
        }
      });

    return () => controller.abort();
  }, [data.tenant_tutor_id, instituicao.id, instituicao.tipo]);

  // estado novo junto aos outros
  const [herancaTutor, setHerancaTutor] = useState(null);

  // useEffect novo, depois do existente
  useEffect(() => {
    if (!data.tenant_tutor_id || !data.curso_id) {
      setHerancaTutor(null);
      return;
    }

    const controller = new AbortController();

    fetch(
      `${cursoDetalhesUrl({ instituicao: instituicao.id }).url}?tenant_tutor_id=${encodeURIComponent(data.tenant_tutor_id)}&curso_id=${encodeURIComponent(data.curso_id)}`,
      { headers: { Accept: 'application/json' }, signal: controller.signal },
    )
      .then((r) => r.json())
      .then((payload) => {
        if (controller.signal.aborted) return;
        const d = payload.data;
        if (!d || !d.nivel_ensino_id) {
          setHerancaTutor(null);
          return;
        }
        setHerancaTutor(d);
        setData((prev) => ({
          ...prev,
          nivel_ensino_id: d.nivel_ensino_id,
          classes: d.classes.map((c) => c.id),
        }));
      })
      .catch((err) => {
        if (err.name === 'AbortError') return;
        setHerancaTutor(null);
      });

    return () => controller.abort();
  }, [data.tenant_tutor_id, data.curso_id]);

  return (
    <div className="mx-auto w-full max-w-sm px-6 py-6 md:max-w-md lg:max-w-195">
      <form onSubmit={onSubmit}>
        <Card className="overflow-visible">
          <CardHeader className="border-b">
            <CardTitle>{title}</CardTitle>
            <CardDescription>
              Preencha os campos abaixo para adicionar um novo curso ao{' '}
              <span className="font-bold">{instituicao.nome}</span>
            </CardDescription>
          </CardHeader>

          <CardContent>
            <FieldGroup>
              <FieldSet>
                {instituicao.tipo === 'colegio' && (
                  <Field>
                    <FieldLabel>Instituição tutora</FieldLabel>
                    <Select
                      value={data.tenant_tutor_id || 'propria'}
                      onValueChange={(value) => {
                        setData((prev) => ({
                          ...prev,
                          tenant_tutor_id: value === 'propria' ? '' : value,
                          curso_id: '',
                          nivel_ensino_id: '', // ← limpa
                          classes: [],
                        }));
                        setHerancaTutor(null);
                      }}
                    >
                      <SelectTrigger className="w-full">
                        <SelectValue placeholder="Seleccione o instituto tutor" />
                      </SelectTrigger>
                      <SelectContent>
                        <SelectGroup>
                          <SelectLabel>Institutos</SelectLabel>
                          <SelectItem value="propria">
                            Tutela própria
                          </SelectItem>
                          {tenantsTutores?.map((tenant) => (
                            <SelectItem key={tenant.id} value={tenant.id}>
                              {tenant.nome}
                            </SelectItem>
                          ))}
                        </SelectGroup>
                      </SelectContent>
                    </Select>
                    {errors.tenant_tutor_id && (
                      <FieldError>{errors.tenant_tutor_id}</FieldError>
                    )}
                  </Field>
                )}

                <Field>
                  <FieldLabel htmlFor="curso_id">Curso</FieldLabel>
                  <Select
                    value={data.curso_id}
                    disabled={carregandoCursos}
                    onValueChange={(value) => {
                      setData('curso_id', String(value));
                    }}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue
                        placeholder={
                          carregandoCursos
                            ? 'A carregar cursos...'
                            : 'Selecione o curso'
                        }
                      />
                    </SelectTrigger>
                    <SelectContent>
                      <SelectGroup>
                        <SelectLabel>
                          {data.tenant_tutor_id
                            ? 'Cursos do instituto tutor'
                            : 'Cursos do catálogo central'}
                        </SelectLabel>
                        {cursosDisponiveis?.map((curso) => (
                          <SelectItem key={curso.id} value={curso.id}>
                            {curso.nome}
                          </SelectItem>
                        ))}
                      </SelectGroup>
                    </SelectContent>
                  </Select>
                  {carregandoCursos && (
                    <p className="text-xs text-muted-foreground">
                      A carregar cursos do instituto tutor...
                    </p>
                  )}
                  {!carregandoCursos && cursosDisponiveis.length === 0 && (
                    <p className="text-xs text-muted-foreground">
                      Nenhum curso disponível para esta tutela.
                    </p>
                  )}
                  {errors.curso_id && (
                    <FieldError>{errors.curso_id}</FieldError>
                  )}
                </Field>

                {herancaTutor ? (
                  <Field>
                    <FieldLabel>Nível de Ensino e Classes</FieldLabel>
                    <div className="flex flex-wrap items-center gap-2">
                      <span className="border bg-muted px-3 py-1.5 text-sm font-medium">
                        {herancaTutor.nivel_ensino_nome}
                      </span>
                      <span className="text-muted-foreground">·</span>
                      {herancaTutor.classes.map((c) => (
                        <span
                          key={c.id}
                          className="border bg-muted px-3 py-1.5 text-sm"
                        >
                          {c.nome}
                        </span>
                      ))}
                      <span className="w-full text-xs text-muted-foreground">
                        Herdados do instituto tutor — não editáveis.
                      </span>
                    </div>
                  </Field>
                ) : (
                  <>
                    <Field>
                      <FieldLabel>Nível de Ensino</FieldLabel>
                      <Select
                        value={data.nivel_ensino_id}
                        onValueChange={(value) =>
                          setData('nivel_ensino_id', value)
                        }
                      >
                        <SelectTrigger className="w-full">
                          <SelectValue placeholder="Selecione o nível de ensino" />
                        </SelectTrigger>
                        <SelectContent>
                          <SelectGroup>
                            <SelectLabel>Níveis de Ensino</SelectLabel>
                            {niveisEnsino?.map((nivel) => (
                              <SelectItem key={nivel.id} value={nivel.id}>
                                {nivel.nome}
                              </SelectItem>
                            ))}
                          </SelectGroup>
                        </SelectContent>
                      </Select>
                      {errors.nivel_ensino_id && (
                        <FieldError>{errors.nivel_ensino_id}</FieldError>
                      )}
                    </Field>

                    <Field>
                      <FieldLabel htmlFor="classes">Classes</FieldLabel>
                      <MultipleSelect
                        placeholder="Selecione as classes"
                        items={classes?.map((classe) => ({
                          value: classe.id,
                          label: classe.nome,
                        }))}
                        onChange={(opts) =>
                          setData(
                            'classes',
                            opts.map((o) => String(o.value)),
                          )
                        }
                        value={(data.classes ?? []).map((id) => ({
                          value: id,
                          label: classes?.find((c) => c.id === id)?.nome ?? id,
                        }))}
                      />
                      {errors.classes && <FieldError>{errors.classes}</FieldError>} 
                    </Field>
                  </>
                )}

                <Field>
                  <Button type="submit" disabled={processing}>
                    Adicionar Curso
                  </Button>

                  <Button
                    variant={'outline'}
                    disabled={processing}
                    onClick={() => window.history.back()}
                  >
                    <ArrowUpLeft />
                    Voltar a lista de cursos
                  </Button>
                </Field>
              </FieldSet>
            </FieldGroup>
          </CardContent>
        </Card>
      </form>
    </div>
  );
}
