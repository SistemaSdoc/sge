import { GrupoPapCards } from './components/grupo-pap-cards';
import { useEffect, useState } from 'react';
import { Head, router } from '@inertiajs/react';
import { Header } from './components/header';
import GrupoPapForm from './components/grupo-pap-form';
import {
  Sheet,
  SheetContent,
  SheetDescription,
  SheetHeader,
  SheetTitle,
} from '@/components/ui/sheet';
import { storeIndependente } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';
import {
  classes as classesRoute,
  turnos as turnosRoute,
  turmas as turmasRoute,
  formOptions as formOptionsRoute,
} from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';

const normalizeFilterValue = (value) =>
  value === null || value === undefined || value === '' ? '' : String(value);

export default function Index({
  instituicao,
  instituicoes,
  cursosTutelados,
  gruposPap,
  anoLectivoId,
  anosLectivos,
  can,
}) {
  const [drawerAberto, setDrawerAberto] = useState(false);

  const [filtroCurso, setFiltroCurso] = useState(() =>
    normalizeFilterValue(null),
  );
  const [filtroInstituicao, setFiltroInstituicao] = useState(() =>
    normalizeFilterValue(instituicao?.id ?? ''),
  );
  const [filtroAnoLectivo, setFiltroAnoLectivo] = useState(() =>
    normalizeFilterValue(anoLectivoId),
  );

  const instituicaoSeleccionada = normalizeFilterValue(
    filtroInstituicao || instituicao?.id || '',
  );
  const anoLectivoSeleccionado = normalizeFilterValue(
    filtroAnoLectivo || anoLectivoId || '',
  );

  useEffect(() => {
    setFiltroInstituicao(
      (prev) => prev || normalizeFilterValue(instituicao?.id ?? ''),
    );
  }, [instituicao?.id]);

  useEffect(() => {
    setFiltroAnoLectivo((prev) => prev || normalizeFilterValue(anoLectivoId));
  }, [anoLectivoId]);

  const [data, setData] = useState({
    curso_tutelado_id: '',
    curso_classe_id: '',
    curso_classe_turno_id: '',
    turma_id: '',
    nome_grupo: '',
    professor_tutor_id: '',
    alunos: [],
  });
  const [classes, setClasses] = useState([]);
  const [turnos, setTurnos] = useState([]);
  const [turmas, setTurmas] = useState([]);
  const [formOptions, setFormOptions] = useState({
    professores: [],
    alunos: [],
  });
  const [errors, setErrors] = useState({});
  const [processing, setProcessing] = useState(false);

  const reset = () => {
    setData({
      curso_tutelado_id: '',
      curso_classe_id: '',
      curso_classe_turno_id: '',
      turma_id: '',
      nome_grupo: '',
      professor_tutor_id: '',
      alunos: [],
    });
    setClasses([]);
    setTurnos([]);
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    setErrors({});
  };

  const setCursoTuteladoId = async (value) => {
    const nextValue = value || '';
    setData((prev) => ({
      ...prev,
      curso_tutelado_id: nextValue,
      curso_classe_id: '',
      curso_classe_turno_id: '',
      turma_id: '',
      professor_tutor_id: '',
      alunos: [],
    }));
    setClasses([]);
    setTurnos([]);
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    if (!nextValue) return;
    try {
      const res = await fetch(
        `${classesRoute(instituicao.id).url}?curso_tutelado_id=${nextValue}`,
      );
      const payload = await res.json();
      setClasses(Array.isArray(payload) ? payload : []);
    } catch {
      setClasses([]);
    }
  };

  const setCursoClasseId = async (value) => {
    const nextValue = value || '';
    setData((prev) => ({
      ...prev,
      curso_classe_id: nextValue,
      curso_classe_turno_id: '',
      turma_id: '',
      professor_tutor_id: '',
      alunos: [],
    }));
    setTurnos([]);
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    if (!nextValue) return;
    try {
      const res = await fetch(
        `${turnosRoute(instituicao.id).url}?curso_classe_id=${nextValue}`,
      );
      const payload = await res.json();
      setTurnos(Array.isArray(payload) ? payload : []);
    } catch {
      setTurnos([]);
    }
  };

  const setCursoClasseTurnoId = async (value) => {
    const nextValue = value || '';
    setData((prev) => ({
      ...prev,
      curso_classe_turno_id: nextValue,
      turma_id: '',
      professor_tutor_id: '',
      alunos: [],
    }));
    setTurmas([]);
    setFormOptions({ professores: [], alunos: [] });
    if (!nextValue) return;
    try {
      const res = await fetch(
        `${turmasRoute(instituicao.id).url}?curso_classe_turno_id=${nextValue}`,
      );
      const payload = await res.json();
      setTurmas(Array.isArray(payload) ? payload : []);
    } catch {
      setTurmas([]);
    }
  };

  const setTurmaId = async (value) => {
    const turmaId = value || '';
    const cursoTuteladoId = data.curso_tutelado_id;
    setData((prev) => ({
      ...prev,
      turma_id: turmaId,
      professor_tutor_id: '',
      alunos: [],
    }));
    if (!turmaId || !cursoTuteladoId) {
      setFormOptions({ professores: [], alunos: [] });
      return;
    }
    try {
      const res = await fetch(
        `${formOptionsRoute(instituicao.id).url}?curso_tutelado_id=${cursoTuteladoId}&turma_id=${turmaId}`,
      );
      const payload = await res.json();
      setFormOptions(payload || { professores: [], alunos: [] });
    } catch {
      setFormOptions({ professores: [], alunos: [] });
    }
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    setProcessing(true);
    router.post(storeIndependente(instituicao.id).url, data, {
      onSuccess: () => {
        setDrawerAberto(false);
      },
      onError: (err) => setErrors(err),
      onFinish: () => setProcessing(false),
    });
  };

  const handleAdicionarGrupo = () => {
    reset();
    setDrawerAberto(true);
  };

  const handleCursoChange = (cursoId) => {
    const nextCursoId = normalizeFilterValue(cursoId);
    setFiltroCurso(nextCursoId);
    router.visit(window.location.pathname, {
      data: {
        curso_tutelado_id: nextCursoId || null,
        ano_lectivo_id: normalizeFilterValue(filtroAnoLectivo),
        instituicao_id: normalizeFilterValue(filtroInstituicao),
      },
      only: ['gruposPap'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleInstituicaoChange = (instituicaoId) => {
    const nextInstituicaoId = normalizeFilterValue(instituicaoId);
    setFiltroInstituicao(nextInstituicaoId);
    setFiltroCurso(null);
    router.visit(window.location.pathname, {
      data: {
        instituicao_id:
          nextInstituicaoId || normalizeFilterValue(instituicao?.id),
        ano_lectivo_id: normalizeFilterValue(filtroAnoLectivo),
        curso_tutelado_id: null,
      },
      only: ['gruposPap', 'cursosTutelados', 'instituicao', 'anoLectivoId'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    const nextAnoLectivoId = normalizeFilterValue(value);
    setFiltroAnoLectivo(nextAnoLectivoId);
    setFiltroCurso(null);
    router.visit(window.location.pathname, {
      data: {
        ano_lectivo_id: nextAnoLectivoId || null,
        curso_tutelado_id: null,
        instituicao_id: normalizeFilterValue(filtroInstituicao),
      },
      only: ['gruposPap', 'cursosTutelados', 'anoLectivoId', 'instituicao'],
      preserveState: true,
      preserveScroll: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Grupos PAP" />

      <Header
        instituicao={instituicao}
        instituicoes={instituicoes}
        cursosTutelados={cursosTutelados}
        filtroInstituicao={instituicaoSeleccionada}
        onInstituicaoChange={handleInstituicaoChange}
        filtroCurso={filtroCurso}
        onCursoChange={handleCursoChange}
        anosLectivos={anosLectivos}
        anoLectivoId={anoLectivoSeleccionado}
        onAnoLectivoChange={handleAnoLectivoChange}
        onAddGrupo={handleAdicionarGrupo}
      />

      <div className="mt-6">
        <GrupoPapCards can={can} grupos={gruposPap.data ?? []} />
      </div>

      <Sheet open={drawerAberto} onOpenChange={setDrawerAberto}>
        <SheetContent className="overflow-y-auto sm:max-w-lg">
          <SheetHeader>
            <SheetTitle>Criar Novo Grupo PAP</SheetTitle>
            <SheetDescription>
              Preenche os dados para criar um novo grupo
            </SheetDescription>
          </SheetHeader>

          <form onSubmit={handleSubmit}>
            <GrupoPapForm
              errors={errors}
              processing={processing}
              cursosTutelados={cursosTutelados}
              classes={classes}
              turnos={turnos}
              turmas={turmas}
              cursoTuteladoId={data.curso_tutelado_id}
              setCursoTuteladoId={setCursoTuteladoId}
              cursoClasseId={data.curso_classe_id}
              setCursoClasseId={setCursoClasseId}
              cursoClasseTurnoId={data.curso_classe_turno_id}
              setCursoClasseTurnoId={setCursoClasseTurnoId}
              turmaId={data.turma_id}
              setTurmaId={setTurmaId}
              nomeGrupo={data.nome_grupo}
              setNomeGrupo={(v) =>
                setData((prev) => ({ ...prev, nome_grupo: v }))
              }
              professores={formOptions.professores}
              alunos={formOptions.alunos}
              professorTutorId={data.professor_tutor_id}
              setProfessorTutorId={(v) =>
                setData((prev) => ({ ...prev, professor_tutor_id: v }))
              }
              alunoIds={data.alunos}
              setAlunoIds={(ids) =>
                setData((prev) => ({ ...prev, alunos: ids }))
              }
              closeDrawer={() => setDrawerAberto(false)}
            />
          </form>
        </SheetContent>
      </Sheet>
    </div>
  );
}
