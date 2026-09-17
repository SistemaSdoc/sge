import { Tabs, TabsContent } from '@/components/ui/tabs';
import { TabAlunos } from './components/tabs/tab-alunos';
import { TabRecurso } from './components/tabs/tab-recurso';
import { TabGruposPAP } from './components/tabs/tab-grupos-pap';
import { TabDisciplinas } from './components/tabs/tab-disciplinas';
import { preview } from '@/actions/App/Http/Controllers/Tenant/ProgressaoController';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/ClasseTurnoTurmaController';
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
  anoLectivoId,
  anosLectivos,
  anoLectivoSelecionado,
}) {
  const { deleteConfirm } = useDialog();

  const alunosPagination = usePagination('alunos');
  const disciplinasPagination = usePagination('disciplinas');
  const gruposPagination = usePagination('grupos');
  const recursosPagination = usePagination('disciplinas');

  const params = {
    instituicao,
    cursoTutelado,
    cursoClasse,
    cursoClasseTurno,
    turma: turma.data.id,
  };

  const classe = turma.data.classe;
  const totalRecurso = pautaRecurso?.resumo?.total ?? 0;

  const handleDelete = (turmaId) => {
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
    <div className="mx-auto w-full max-w-6xl space-y-4 p-4 md:p-6">
      <Header
        can={can}
        turma={turma.data}
        disciplinas={disciplinas}
        alunos={alunos}
        totalRecurso={totalRecurso}
        preview={preview}
        params={params}
        anoLectivoId={anoLectivoId}
        anosLectivos={anosLectivos}
        deleteFn={handleDelete}
      />

      <Tabs defaultValue="disciplinas" className="w-full">
        {/* Wrapper com scroll smooth no mobile */}
        <div className="md:overflow-none overflow-x-auto">
          <TurmaTabsList classe={classe} totalRecurso={totalRecurso} />
        </div>

        <TabsContent value="alunos" className="mt-2 space-y-4">
          <TabAlunos
            can={can.alunos}
            alunos={alunos.data}
            params={params}
            pagination={alunos.meta}
            onPageChange={alunosPagination.handlePageChange}
          />
        </TabsContent>

        <TabsContent value="disciplinas" className="mt-2 space-y-4">
          <TabDisciplinas
            can={can.disciplinas}
            turma={turma}
            disciplinas={disciplinas.data}
            anoLectivoId={anoLectivoId}
            params={params}
            redirectTo={window.location.href}
            pagination={disciplinas.meta}
            onPageChange={disciplinasPagination.handlePageChange}
            anoLectivoSelecionado={anoLectivoSelecionado}
          />
        </TabsContent>

        {classe?.nome === '13ª' && (
          <TabsContent value="grupos-pap" className="mt-2 space-y-4">
            <TabGruposPAP
              can={can.grupos}
              turma={turma.data}
              grupos={grupos.data}
              params={params}
              pagination={grupos.meta}
              onPageChange={gruposPagination.handlePageChange}
            />
          </TabsContent>
        )}

        {totalRecurso > 0 && (
          <TabsContent value="recurso" className="mt-2 space-y-4">
            <TabRecurso
              disciplinas={disciplinas.data}
              params={params}
              pagination={disciplinas.meta}
              onPageChange={recursosPagination.handlePageChange}
              podeLancarRecurso={pode_lancar_recurso}
            />
          </TabsContent>
        )}
      </Tabs>
    </div>
  );
}
