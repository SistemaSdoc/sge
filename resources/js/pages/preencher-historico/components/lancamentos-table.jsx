import { useForm } from '@inertiajs/react';
import { useState } from 'react';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
  CardAction,
} from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ArrowLeft, Loader2, LockKeyhole, LockKeyholeOpen } from 'lucide-react';
import { mediaTrimestral } from '@/utils/media-trimestral';

export default function Create({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  classeTurnoDisciplina,
  can,
}) {
  // DADOS MOCKADOS - Aluno e Turma
  const mockAluno = {
    id: '019fe147-3b48-70fa-9505-2830d4ead9c6',
    nome: 'Paulina Capitão',
    matricula: 'MAT-2026-0002',
  };

  const mockTurmaAluno = {
    id: '019fdba5-beed-70be-b1d0-032538d735b6',
  };

  const mockTurma = {
    id: '019fdba4-9ed2-71de-83ca-5918e60f0f8e',
    nome: 'A',
  };

  const mockClasse = {
    nome: '10ª',
  };

  const mockAnoLectivo = {
    nome: '2024/2025',
  };

  // DADOS MOCKADOS - Disciplinas
  const mockDisciplinas = [
    { id: '1', nome: 'Português', sigla: 'PT' },
    { id: '2', nome: 'Matemática', sigla: 'MAT' },
    { id: '3', nome: 'História', sigla: 'HIST' },
    { id: '4', nome: 'Geografia', sigla: 'GEO' },
    { id: '5', nome: 'Inglês', sigla: 'ING' },
    { id: '6', nome: 'Educação Física', sigla: 'EF' },
  ];

  // DADOS MOCKADOS - Notas iniciais
  const mockNotasIniciais = {
    1: {
      1: { mac: 14, npp: 15, npt: 14, faltas: 2 },
      2: { mac: 15, npp: 16, npt: 15, faltas: 1 },
      3: { mac: 16, npp: 17, npt: 16, faltas: 0 },
    },
    2: {
      1: { mac: 12, npp: 13, npt: 12, faltas: 3 },
      2: { mac: 13, npp: 14, npt: 13, faltas: 2 },
      3: { mac: 14, npp: 15, npt: 14, faltas: 1 },
    },
    3: {
      1: { mac: 15, npp: 16, npt: 15, faltas: 1 },
      2: { mac: 16, npp: 17, npt: 16, faltas: 0 },
      3: { mac: 17, npp: 18, npt: 17, faltas: 0 },
    },
    4: {
      1: { mac: 13, npp: 14, npt: 13, faltas: 2 },
      2: { mac: 14, npp: 15, npt: 14, faltas: 1 },
      3: { mac: 15, npp: 16, npt: 15, faltas: 0 },
    },
    5: {
      1: { mac: 16, npp: 17, npt: 16, faltas: 0 },
      2: { mac: 17, npp: 18, npt: 17, faltas: 0 },
      3: { mac: 18, npp: 19, npt: 18, faltas: 0 },
    },
    6: {
      1: { mac: 18, npp: 19, npt: 18, faltas: 0 },
      2: { mac: 19, npp: 20, npt: 19, faltas: 0 },
      3: { mac: 20, npp: 20, npt: 20, faltas: 0 },
    },
  };

  const { data, setData, post, processing, errors } = useForm({
    turma_aluno_id: mockTurmaAluno.id,
    notas: mockNotasIniciais,
  });

  const [periodo, setPeriodo] = useState('1');
  const [expandidos, setExpandidos] = useState({});
  const [todosAbertos, setTodosAbertos] = useState(false);

  const toggleTodos = () => {
    if (todosAbertos) {
      setExpandidos({});
      setTodosAbertos(false);
    } else {
      const todos = {};
      mockDisciplinas.forEach((d) => {
        todos[d.id] = true;
      });
      setExpandidos(todos);
      setTodosAbertos(true);
    }
  };

  const toggleDisciplina = (disciplinaId) => {
    setExpandidos((prev) => ({
      ...prev,
      [disciplinaId]: !prev[disciplinaId],
    }));
  };

  const setNota = (disciplinaId, campo, valor) => {
    setData((prev) => ({
      ...prev,
      notas: {
        ...prev.notas,
        [disciplinaId]: {
          ...prev.notas[disciplinaId],
          [periodo]: {
            ...prev.notas[disciplinaId]?.[periodo],
            [campo]: valor,
          },
        },
      },
    }));
  };

  const getNota = (disciplinaId, campo) => {
    return data.notas[disciplinaId]?.[periodo]?.[campo] ?? '';
  };

  const handleSubmit = (accao) => {
    const dadosEnvio = {
      turma_aluno_id: mockTurmaAluno.id,
      periodo: parseInt(periodo),
      notas: mockDisciplinas.reduce((acc, disciplina) => {
        acc[disciplina.id] = {
          mac: getNota(disciplina.id, 'mac'),
          npp: getNota(disciplina.id, 'npp'),
          npt: getNota(disciplina.id, 'npt'),
          faltas: getNota(disciplina.id, 'faltas'),
        };
        return acc;
      }, {}),
      accao,
    };
    console.log('Dados a enviar:', dadosEnvio);
    // post(route('nota.store-historico', { turmaAluno: mockTurmaAluno.id }), {
    //   preserveScroll: true,
    // });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      {/* Header */}

      <Card>
        <CardHeader>
          <CardTitle>Lançamento de Histórico Académico</CardTitle>
          <CardDescription>
            Aluno: <strong>{mockAluno.nome}</strong> ({mockAluno.matricula}) |
            Classe: <strong>{mockClasse.nome}</strong> | Turma:{' '}
            <strong>{mockTurma.nome}</strong> | Ano:{' '}
            <strong>{mockAnoLectivo.nome}</strong>
          </CardDescription>

          <CardAction>
            <Button variant={'outline'} size={'sm'}>
              <ArrowLeft />
              Voltar
            </Button>
          </CardAction>
        </CardHeader>
      </Card>

      {/* Tabela de Disciplinas */}
      <Card className="gap-0">
        <CardHeader className="border-b">
          <div>
            <CardTitle>Disciplinas</CardTitle>
            <CardDescription>
              Preencha as notas para o trimestre selecionado.
            </CardDescription>
          </div>

          <CardAction className="flex items-center gap-3">
            {/* Toggle global */}
            <Button
              type="button"
              variant="ghost"
              size="sm"
              onClick={toggleTodos}
            >
              {todosAbertos ? (
                <>
                  <LockKeyhole className="mr-1 size-4" />
                  Fechar todos
                </>
              ) : (
                <>
                  <LockKeyholeOpen className="mr-1 size-4" />
                  Abrir todos
                </>
              )}
            </Button>

            {/* Select Trimestre */}
            <Select value={periodo} onValueChange={setPeriodo}>
              <SelectTrigger className="w-40">
                <SelectValue placeholder="Trimestre" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="1">1º Trimestre</SelectItem>
                <SelectItem value="2">2º Trimestre</SelectItem>
                <SelectItem value="3">3º Trimestre</SelectItem>
              </SelectContent>
            </Select>

            {/* Botões */}
            <Button
              type="button"
              variant="outline"
              disabled={processing}
              onClick={() => handleSubmit('guardar')}
            >
              Guardar rascunho
            </Button>
            <Button
              type="button"
              disabled={processing}
              onClick={() => handleSubmit('finalizar')}
            >
              {processing && <Loader2 className="mr-2 size-4 animate-spin" />}
              Finalizar lançamento
            </Button>
          </CardAction>
        </CardHeader>

        <CardContent className="p-0">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="w-48 px-4">Disciplina</TableHead>
                <TableHead className="w-20 px-4 text-center">MAC</TableHead>
                <TableHead className="w-20 px-4 text-center">NPP</TableHead>
                <TableHead className="w-20 px-4 text-center">NPT</TableHead>
                <TableHead className="w-20 px-4 text-center">MT</TableHead>
                <TableHead className="w-8 px-2" />
              </TableRow>
            </TableHeader>

            <TableBody>
              {mockDisciplinas.map((disciplina) => {
                const mac = getNota(disciplina.id, 'mac');
                const npp = getNota(disciplina.id, 'npp');
                const npt = getNota(disciplina.id, 'npt');
                const mt = mediaTrimestral(mac, npp, npt);
                const aberto = expandidos[disciplina.id];

                return (
                  <TableRow key={disciplina.id}>
                    <TableCell className="px-4 font-medium">
                      {disciplina.nome}
                    </TableCell>

                    {/* MAC */}
                    <TableCell className="px-4">
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          step={0.1}
                          value={mac}
                          disabled={processing}
                          onChange={(e) =>
                            setNota(disciplina.id, 'mac', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {mac !== '' ? mac : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* NPP */}
                    <TableCell className="px-4">
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          step={0.1}
                          value={npp}
                          disabled={processing}
                          onChange={(e) =>
                            setNota(disciplina.id, 'npp', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {npp !== '' ? npp : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* NPT */}
                    <TableCell className="px-4">
                      {aberto ? (
                        <Input
                          type="number"
                          min={0}
                          max={20}
                          step={0.1}
                          value={npt}
                          disabled={processing}
                          onChange={(e) =>
                            setNota(disciplina.id, 'npt', e.target.value)
                          }
                          className="text-center"
                        />
                      ) : (
                        <span className="block text-center text-sm text-muted-foreground">
                          {npt !== '' ? npt : '-'}
                        </span>
                      )}
                    </TableCell>

                    {/* MT (calculada) */}
                    <TableCell className="px-4 text-center font-semibold">
                      {mt ?? '-'}
                    </TableCell>

                    {/* Toggle individual */}
                    <TableCell className="px-2">
                      <Button
                        type="button"
                        variant="ghost"
                        size="icon"
                        className="h-6 w-6"
                        onClick={() => toggleDisciplina(disciplina.id)}
                      >
                        {aberto ? (
                          <LockKeyholeOpen className="size-4" />
                        ) : (
                          <LockKeyhole className="size-4" />
                        )}
                      </Button>
                    </TableCell>
                  </TableRow>
                );
              })}
            </TableBody>
          </Table>
        </CardContent>
      </Card>
    </div>
  );
}
