import { useState, useEffect } from 'react';
import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { Loader2 } from 'lucide-react';

export default function Preencher({ aluno, classesFaltando, anosLectivos }) {
  const { data, setData, post, processing, errors } = useForm({
    turma_id: '',
  });

  const [selectedAno, setSelectedAno] = useState('');
  const [selectedClasse, setSelectedClasse] = useState('');
  const [selectedTurno, setSelectedTurno] = useState('');

  const [turnos, setTurnos] = useState([]);
  const [turmas, setTurmas] = useState([]);

  useEffect(() => {
    if (selectedAno && selectedClasse) {
      fetch(
        `/dashboard/historico/turnos?ano_lectivo_id=${selectedAno}&curso_classe_id=${selectedClasse}`,
      )
        .then((res) => {
          if (!res.ok) {
            throw new Error(`Erro ${res.status}: ${res.statusText}`);
          }
          return res.json();
        })
        .then((data) => {
          console.log('Turnos carregados:', data);
          setTurnos(data);
          setSelectedTurno('');
          setTurmas([]);
        })
        .catch((err) => {
          console.error('Erro ao carregar turnos:', err);
          setTurnos([]);
        });
    }
  }, [selectedAno, selectedClasse]);

  useEffect(() => {
    if (selectedAno && selectedTurno) {
      fetch(
        `/dashboard/historico/turmas?ano_lectivo_id=${selectedAno}&curso_classe_turno_id=${selectedTurno}`,
      )
        .then((res) => {
          if (!res.ok) {
            throw new Error(`Erro ${res.status}: ${res.statusText}`);
          }
          return res.json();
        })
        .then((data) => {
          console.log('Turmas carregadas:', data);
          setTurmas(data);
        })
        .catch((err) => {
          console.error('Erro ao carregar turmas:', err);
          setTurmas([]);
        });
    }
  }, [selectedAno, selectedTurno]);

  const handleSubmit = (e) => {
    e.preventDefault();
    post(route('historico.confirmar', { aluno: aluno.id }));
  };

  return (
    <form onSubmit={handleSubmit} className="space-y-6">
      {/* Info */}
      <div className="space-y-1 rounded-lg bg-slate-50 p-3 dark:bg-slate-900/30">
        <div className="text-sm font-medium">{aluno.nome}</div>
        <div className="text-xs text-muted-foreground">
          Matrícula: {aluno.matricula}
        </div>
      </div>

      <FieldGroup>
        <div className="space-y-4">
          {/* Ano Lectivo */}
          <Field>
            <FieldLabel htmlFor="ano">
              Ano Lectivo <span className="text-red-500">*</span>
            </FieldLabel>
            <Select value={selectedAno} onValueChange={setSelectedAno}>
              <SelectTrigger id="ano">
                <SelectValue placeholder="Selecione um ano..." />
              </SelectTrigger>
              <SelectContent>
                {anosLectivos.map((ano) => (
                  <SelectItem key={ano.id} value={ano.id}>
                    {ano.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Classe (apenas as que faltam) */}
          <Field>
            <FieldLabel htmlFor="classe">
              Classe <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={selectedClasse}
              onValueChange={setSelectedClasse}
              disabled={!selectedAno}
            >
              <SelectTrigger id="classe">
                <SelectValue placeholder="Selecione uma classe..." />
              </SelectTrigger>
              <SelectContent>
                {classesFaltando.map((classe) => (
                  <SelectItem
                    key={classe.curso_classe_id}
                    value={classe.curso_classe_id}
                  >
                    {classe.classe}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Turno (filtrado por classe) */}
          <Field>
            <FieldLabel htmlFor="turno">
              Turno <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={selectedTurno}
              onValueChange={setSelectedTurno}
              disabled={!selectedClasse || turnos.length === 0}
            >
              <SelectTrigger id="turno">
                <SelectValue
                  placeholder={
                    !selectedClasse
                      ? 'Selecione uma classe primeiro...'
                      : turnos.length === 0
                        ? 'Nenhum turno disponível'
                        : 'Selecione um turno...'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {turnos.map((turno) => (
                  <SelectItem key={turno.id} value={turno.id}>
                    {turno.turno_nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </Field>

          {/* Turma (filtrada por turno) */}
          <Field>
            <FieldLabel htmlFor="turma">
              Turma <span className="text-red-500">*</span>
            </FieldLabel>
            <Select
              value={data.turma_id}
              onValueChange={(val) => setData('turma_id', val)}
              disabled={!selectedTurno || turmas.length === 0}
            >
              <SelectTrigger id="turma">
                <SelectValue
                  placeholder={
                    !selectedTurno
                      ? 'Selecione um turno primeiro...'
                      : turmas.length === 0
                        ? 'Nenhuma turma disponível'
                        : 'Selecione uma turma...'
                  }
                />
              </SelectTrigger>
              <SelectContent>
                {turmas.map((turma) => (
                  <SelectItem key={turma.id} value={turma.id}>
                    {turma.nome}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
            {errors.turma_id && <FieldError>{errors.turma_id}</FieldError>}
          </Field>
        </div>
      </FieldGroup>

      {/* Botões */}
      <div className="flex justify-end gap-3 pt-4">
        <Button type="button" variant="outline" disabled={processing}>
          Cancelar
        </Button>
        <Button type="submit" disabled={!data.turma_id || processing}>
          {processing && <Loader2 className="mr-2 size-4 animate-spin" />}
          Confirmar
        </Button>
      </div>
    </form>
  );
}
