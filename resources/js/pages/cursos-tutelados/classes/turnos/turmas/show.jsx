import { Tabs, TabsContent } from '@/components/ui/tabs';
import { TabAlunos } from './components/tabs/tab-alunos';
import { TabRecurso } from './components/tabs/tab-recurso';
import { TabGruposPAP } from './components/tabs/tab-grupos-pap';
import { TabDisciplinas } from './components/tabs/tab-disciplinas';
import { preview } from '@/actions/App/Http/Controllers/ProgressaoController';
import { destroy } from '@/actions/App/Http/Controllers/ClasseTurnoTurmaController';
import { Header } from './components/turma-header';
import { TurmaTabsList } from './components/tabs/tab-list';
import { usePagination } from '@/hooks/use-pagination';
import { useDialog } from '@/hooks/use-dialog';
import { router } from '@inertiajs/react';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  alunos,
  disciplinas,
  pautaRecurso,
  pode_lancar_recurso,
  grupos,
  can,
}) {
  const { deleteConfirm } = useDialog();

  const alunosPagination = usePagination('alunos');
  const disciplinasPagination = usePagination('disciplinas');
  const gruposPagination = usePagination('grupos');
  const recursosPagination = usePagination('disciplinas');

  const params = {
    instituicao: instituicao.id,
    cursoTutelado: cursoTutelado.id,
    cursoClasse: cursoClasse.id,
    cursoClasseTurno: cursoClasseTurno.id,
    turma: turma.data.id,
  };

  const classe = turma.data.classe;
  const totalRecurso = pautaRecurso?.resumo?.total ?? 0;

  const handleDelete = (turmaId) => {
    console.log('params:', params);
    console.log('turmaId:', turmaId);
    deleteConfirm({
      title: 'Tens a certeza?',
      description: 'Esta acção é irreversível. A turma será removida.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroy({
            ...params,
            turma: turmaId,
          }).url,
        ),
    });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Header
        turma={turma.data}
        disciplinas={disciplinas}
        alunos={alunos}
        totalRecurso={totalRecurso}
        preview={preview}
        routeParams={params}
        params={params}
        deleteFn={handleDelete}
      />

      <Tabs defaultValue="alunos" className="w-full">
        <TurmaTabsList classe={classe} totalRecurso={totalRecurso} />

        <TabsContent value="alunos" className="mt-2">
          <TabAlunos
            alunos={alunos.data}
            pagination={alunos.meta}
            onPageChange={alunosPagination.handlePageChange}
            params={params}
            can={can.alunos}
          />
        </TabsContent>

        <TabsContent value="disciplinas" className="mt-2">
          <TabDisciplinas
            disciplinas={disciplinas.data}
            turma={turma}
            pagination={disciplinas.meta}
            onPageChange={disciplinasPagination.handlePageChange}
            params={params}
            redirectTo={window.location.href}
            can={can.disciplinas}
          />
        </TabsContent>

        {classe?.nome === '13ª' && (
          <TabsContent value="grupos-pap" className="mt-2">
            <TabGruposPAP
              turma={turma.data}
              grupos={grupos.data}
              instituicaoId={params.instituicao}
              cursoTuteladoId={params.cursoTutelado}
              cursoClasseId={params.cursoClasse}
              cursoClasseTurnoId={params.cursoClasseTurno}
              pagination={grupos.meta}
              onPageChange={gruposPagination.handlePageChange}
              params={params}
              can={can.grupos}
            />
          </TabsContent>
        )}

        {totalRecurso > 0 && (
          <TabsContent value="recurso">
            <TabRecurso
              disciplinas={disciplinas.data}
              params={params}
              pagination={disciplinas.meta}
              onPageChange={disciplinasPagination.handlePageChange}
              podeLancarRecurso={pode_lancar_recurso}
            />
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
