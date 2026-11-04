import { useForm, useHttp } from '@inertiajs/react';
import { Eye, LoaderCircle } from 'lucide-react';
import type { FormEvent } from 'react';
import { useState } from 'react';
import {
  gerar,
  previsualizar,
} from '@/actions/App/Http/Controllers/Tenant/QuadroHonraController';
import {
  Alert,
  AlertTitle,
} from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import type {
  AnoLectivoOption,
  CandidatoQuadroHonra,
  CursoOption,
  PrevisualizacaoQuadroHonra,
} from '@/types/quadro-honra';

type FormularioQuadroHonra = {
  tipo: 'geral' | 'curso';
  ano_lectivo_id: string;
  curso_tutelado_id: string;
  media_minima: string;
  limite_alunos: string;
};

type FormGerarQuadroHonraProps = {
  anosLectivos: AnoLectivoOption[];
  anoAtivoId: string | null;
  cursos: CursoOption[];
  podeGerarGeral: boolean;
  podeGerarCurso: boolean;
};

/** Permite iniciar uma geração com os critérios validados pelo servidor. */
export function FormGerarQuadroHonra({
  anosLectivos,
  anoAtivoId,
  cursos,
  podeGerarGeral,
  podeGerarCurso,
}: FormGerarQuadroHonraProps) {
  const tipoInicial = podeGerarGeral ? 'geral' : 'curso';
  const form = useForm<FormularioQuadroHonra>({
    tipo: tipoInicial,
    ano_lectivo_id: anoAtivoId ?? '',
    curso_tutelado_id: '',
    media_minima: '14',
    limite_alunos: '10',
  });
  const preview = useHttp<FormularioQuadroHonra, PrevisualizacaoQuadroHonra>(
    form.data,
  );
  const [candidatos, setCandidatos] = useState<CandidatoQuadroHonra[] | null>(
    null,
  );
  const [erroGeracao, setErroGeracao] = useState<string | null>(null);

  const submeter = (event: FormEvent<HTMLFormElement>) => {
    event.preventDefault();
    form.post(gerar().url, {
      preserveScroll: true,
      onError: (errors) => setErroGeracao(errors.geracao ?? null),
      onSuccess: () => setErroGeracao(null),
    });
  };

  const limparPrevisualizacao = () => {
    form.clearErrors();
    preview.clearErrors();
    setCandidatos(null);
    setErroGeracao(null);
  };

  const atualizarCampo = (
    campo: Exclude<keyof FormularioQuadroHonra, 'tipo'>,
    valor: string,
  ) => {
    const dadosAtualizados = { ...form.data, [campo]: valor };
    form.setData(dadosAtualizados);
    preview.setData(dadosAtualizados);
    limparPrevisualizacao();
  };

  const atualizarTipo = (tipo: FormularioQuadroHonra['tipo']) => {
    const dadosAtualizados = {
      ...form.data,
      tipo,
      curso_tutelado_id:
        tipo === 'geral' ? '' : form.data.curso_tutelado_id,
    };
    form.setData(dadosAtualizados);
    preview.setData(dadosAtualizados);
    limparPrevisualizacao();
  };

  const submeterPrevisualizacao = () => {
    setCandidatos(null);
    preview.post(previsualizar().url, {
      onSuccess: (resultado) => setCandidatos(resultado.candidatos),
    });
  };

  const erroCampo = (campo: keyof FormularioQuadroHonra) =>
    form.errors[campo] ?? preview.errors[campo];

  return (
    <Card>
      <CardHeader>
        <CardTitle>Gerar Quadro de Honra</CardTitle>
        <CardDescription>
          A média mínima de elegibilidade é 14 valores. Alunos que foram a
          recurso são excluídos.
        </CardDescription>
      </CardHeader>
      <CardContent>
        <form
          onSubmit={submeter}
          className="grid gap-4 md:grid-cols-2 xl:grid-cols-5"
        >
          {(podeGerarGeral || podeGerarCurso) && (
            <div className="space-y-2">
              <Label htmlFor="tipo-quadro">Tipo de quadro de honra</Label>
              <Select
                value={form.data.tipo}
                onValueChange={(tipo: string) => {
                  atualizarTipo(tipo as FormularioQuadroHonra['tipo']);
                }}
                disabled={!podeGerarGeral || !podeGerarCurso}
              >
                <SelectTrigger id="tipo-quadro" className="w-full">
                  <SelectValue placeholder="Selecione o quadro" />
                </SelectTrigger>
                <SelectContent>
                  {podeGerarGeral && (
                    <SelectItem value="geral">Geral</SelectItem>
                  )}
                  {podeGerarCurso && (
                    <SelectItem value="curso">Por curso</SelectItem>
                  )}
                </SelectContent>
              </Select>
              {erroCampo('tipo') && (
                <p className="text-xs text-destructive">
                  {erroCampo('tipo')}
                </p>
              )}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="ano-lectivo">Ano lectivo</Label>
            <Select
              value={form.data.ano_lectivo_id}
              onValueChange={(ano) =>
                atualizarCampo('ano_lectivo_id', ano)
              }
            >
              <SelectTrigger id="ano-lectivo" className="w-full">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={ano.id}>
                    {ano.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {erroCampo('ano_lectivo_id') && (
              <p className="text-xs text-destructive">
                {erroCampo('ano_lectivo_id')}
              </p>
            )}
          </div>

          {form.data.tipo === 'curso' && (
            <div className="space-y-2">
              <Label htmlFor="curso-tutelado">Curso</Label>
              <Select
                value={form.data.curso_tutelado_id}
                onValueChange={(curso) =>
                  atualizarCampo('curso_tutelado_id', curso)
                }
              >
                <SelectTrigger id="curso-tutelado" className="w-full">
                  <SelectValue placeholder="Selecione o curso" />
                </SelectTrigger>
                <SelectContent>
                  {cursos.map((curso) => (
                    <SelectItem key={curso.id} value={curso.id}>
                      {curso.nome}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
              {erroCampo('curso_tutelado_id') && (
                <p className="text-xs text-destructive">
                  {erroCampo('curso_tutelado_id')}
                </p>
              )}
            </div>
          )}

          <div className="space-y-2">
            <Label htmlFor="media-minima">Média mínima</Label>
            <Input
              id="media-minima"
              type="number"
              min={14}
              max={20}
              step="0.1"
              value={form.data.media_minima}
              onChange={(event) =>
                atualizarCampo('media_minima', event.target.value)
              }
              aria-invalid={Boolean(erroCampo('media_minima'))}
            />
            {erroCampo('media_minima') && (
              <p className="text-xs text-destructive">
                {erroCampo('media_minima')}
              </p>
            )}
          </div>

          <div className="space-y-2">
            <Label htmlFor="limite-alunos">Limite de alunos</Label>
            <Input
              id="limite-alunos"
              type="number"
              min={1}
              max={65535}
              step={1}
              value={form.data.limite_alunos}
              onChange={(event) =>
                atualizarCampo('limite_alunos', event.target.value)
              }
              aria-invalid={Boolean(erroCampo('limite_alunos'))}
            />
            {erroCampo('limite_alunos') && (
              <p className="text-xs text-destructive">
                {erroCampo('limite_alunos')}
              </p>
            )}
          </div>

          <div className="md:col-span-2 xl:col-span-5">
            <div className="flex flex-wrap gap-2">
              <Button
                type="button"
                variant="outline"
                disabled={
                  preview.processing ||
                  form.processing ||
                  anosLectivos.length === 0 ||
                  (form.data.tipo === 'curso' && cursos.length === 0)
                }
                onClick={submeterPrevisualizacao}
              >
                {preview.processing ? (
                  <LoaderCircle className="size-4 animate-spin" />
                ) : (
                  <Eye className="size-4" />
                )}
                {preview.processing
                  ? 'A procurar alunos…'
                  : 'Pré-visualizar elegíveis'}
              </Button>
              <Button
                type="submit"
                disabled={
                  form.processing ||
                  preview.processing ||
                  !candidatos?.length
                }
              >
                {form.processing && (
                  <LoaderCircle className="size-4 animate-spin" />
                )}
                {form.processing ? 'A gerar…' : 'Gerar quadro'}
              </Button>
            </div>
            {erroGeracao && (
              <p className="mt-2 text-sm text-destructive">
                {erroGeracao}
              </p>
            )}
          </div>
        </form>

        {candidatos !== null && (
          <section className="mt-6 space-y-3" aria-live="polite">
            {candidatos.length === 0 ? (
              <Alert variant="warning">
                <AlertTitle>Não há alunos elegíveis</AlertTitle>
              </Alert>
            ) : (
              <>
                <div>
                  <h3 className="font-medium">
                    Pré-visualização: {candidatos.length}{' '}
                    {candidatos.length === 1
                      ? 'aluno elegível'
                      : 'alunos elegíveis'}
                  </h3>
                  <p className="text-sm text-muted-foreground">
                    Esta pré-visualização não cria nem guarda o quadro.
                  </p>
                </div>
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Posição</TableHead>
                      <TableHead>Aluno</TableHead>
                      <TableHead>Curso</TableHead>
                      <TableHead>Classe</TableHead>
                      <TableHead>Turma</TableHead>
                      <TableHead>Média</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {candidatos.map((candidato, index) => (
                      <TableRow key={candidato.turma_aluno_id}>
                        <TableCell>{index + 1}</TableCell>
                        <TableCell>{candidato.nome_aluno}</TableCell>
                        <TableCell>{candidato.curso}</TableCell>
                        <TableCell>{candidato.classe}</TableCell>
                        <TableCell>{candidato.turma}</TableCell>
                        <TableCell>
                          {Number(candidato.media).toLocaleString('pt-AO')}{' '}
                          valores
                        </TableCell>
                      </TableRow>
                    ))}
                  </TableBody>
                </Table>
              </>
            )}
          </section>
        )}
      </CardContent>
    </Card>
  );
}
