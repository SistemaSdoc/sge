import { router } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TabTurmas } from './components/tabs/tab-turmas';
import { TabProfessores } from './components/tabs/tab-professores';
import { TabSecretarios } from './components/tabs/tab-secretarios';
import { TabCriteriosPap } from './components/tabs/tab-criteriospap';
import { TabSugestoesTemas } from './components/tabs/tab-sugestoes-temas';
import { Badge } from '@/components/ui/badge';
import { show as showClasse } from '@/actions/App/Http/Controllers/Tenant/CursoClasseController';
import {
  edit,
  show as showCurso,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
import { destroy as destroySecretario } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoSecretarioController';
import { useDialog } from '@/hooks/use-dialog';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { Header } from './components/curso-header';
import { useState } from 'react';

export default function Show({
  instituicao,
  cursoTutelado,
  anoLectivoId,
  anosLectivos = [],
  secretariosDisponiveis = [],
  can,
  errors = {},
}) {
  const { deleteConfirm } = useDialog();
  const [activeTab, setActiveTab] = useState('turmas');

  const params = {
    instituicao,
    cursoTutelado,
  };

  const handleDeleteProfessor = (vinculoId) => {
    deleteConfirm({
      title: 'Tens a certeza?',
      description:
        'Esta acção é irreversível. O professor será removido do curso.',
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroy({
            ...params,
            professor: vinculoId,
          }).url,
        ),
    });
  };

  const handleDeleteSecretario = (userId, nome) => {
    deleteConfirm({
      title: 'Remover secretário do curso?',
      description: `${nome} deixará de ter acesso associado a este curso.`,
      confirmLabel: 'Remover',
      confirmFn: () =>
        router.delete(
          destroySecretario({
            ...params,
            secretario: userId,
          }).url,
          { preserveScroll: true },
        ),
    });
  };

  const handlePageChange = (param) => (page) => {
    router.visit(showCurso({ ...params }).url, {
      data: {
        page_turmas: cursoTutelado.turmas?.current_page ?? 1,
        page_professores: cursoTutelado.professores?.current_page ?? 1,
        page_sugestoes: cursoTutelado.sugestoes_temas?.current_page ?? 1,
        ano_lectivo_id: anoLectivoId,
        [param]: page,
      },
      preserveScroll: true,
      preserveState: true,
    });
  };

  const handleAnoLectivoChange = (value) => {
    router.visit(showCurso({ ...params }).url, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
      preserveState: true,
    });
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-4 p-4 md:p-6">
      <Header can={can} params={params} />

      {/* Tabs */}
      <Tabs value={activeTab} onValueChange={setActiveTab}>
        <div className="flex w-full flex-col gap-3 md:flex md:flex-row md:justify-between">
          <TabsList className="order-2 w-auto max-md:grid max-md:h-auto! max-md:w-full max-md:grid-cols-2 max-md:gap-1 md:order-1">
            <TabsTrigger
              value="turmas"
              className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
            >
              Turmas
            </TabsTrigger>
            <TabsTrigger
              value="professores"
              className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
            >
              Professores
            </TabsTrigger>
            {cursoTutelado.can?.manageSecretarios && (
              <TabsTrigger
                value="secretarios"
                className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
              >
                Secretários
              </TabsTrigger>
            )}
            <TabsTrigger
              value="criterios-pap"
              className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
            >
              Critérios para a PAP
            </TabsTrigger>
            <TabsTrigger
              value="sugestoes-temas"
              className="hover:cursor-pointer max-md:h-auto! max-md:min-h-8 max-md:min-w-0 max-md:text-center max-md:leading-tight max-md:whitespace-normal"
            >
              Sugestões de temas
            </TabsTrigger>
          </TabsList>

          <div className="order-1 md:order-2">
            <Select
              value={anoLectivoId ?? ''}
              onValueChange={handleAnoLectivoChange}
            >
              <SelectTrigger className="w-full md:w-auto">
                <SelectValue placeholder="Selecione o ano lectivo" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Anos Lectivos</SelectLabel>
                  {anosLectivos?.map((ano) => (
                    <SelectItem key={ano.id} value={ano.id}>
                      {ano.nome}
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>
        </div>

        <TabsContent value="turmas" className="mt-2">
          <TabTurmas
            params={params}
            turmas={cursoTutelado.turmas}
            can={cursoTutelado.can}
            anoLectivoId={anoLectivoId}
            pagination={cursoTutelado?.turmas}
            onPageChange={handlePageChange('page_turmas')}
          />
        </TabsContent>

        <TabsContent value="professores" className="mt-2">
          <TabProfessores
            params={params}
            professores={cursoTutelado.professores}
            can={cursoTutelado.can}
            deleteFn={handleDeleteProfessor}
            canAttachSecretario={cursoTutelado.can?.attachSecretario}
            onAddSecretario={() => setActiveTab('secretarios')}
            pagination={cursoTutelado?.professores}
            onPageChange={handlePageChange('page_professores')}
          />
        </TabsContent>

        {cursoTutelado.can?.manageSecretarios && (
          <TabsContent value="secretarios" className="mt-2">
            <TabSecretarios
              params={params}
              secretarios={cursoTutelado.secretarios ?? []}
              disponiveis={secretariosDisponiveis}
              canAttach={cursoTutelado.can?.attachSecretario}
              removeFn={handleDeleteSecretario}
            />
          </TabsContent>
        )}

        <TabsContent value="criterios-pap" className="mt-2">
          <TabCriteriosPap
            params={params}
            criteriosPapUrl={cursoTutelado.criterios_pap_url}
            manualPtUrl={cursoTutelado.manual_pt_url}
            estruturaTrabalhoPapUrl={cursoTutelado.estrutura_trabalho_pap_url}
            sugestoesTemaPapUrl={cursoTutelado.sugestoes_temas_pap_url}
            //can={cursoTutelado.can}
            can={can}
            errors={errors}
          />
        </TabsContent>

        <TabsContent value="sugestoes-temas" className="mt-2">
          <TabSugestoesTemas
            params={params}
            sugestoes={cursoTutelado.sugestoes_temas?.data ?? []}
            pagination={cursoTutelado.sugestoes_temas}
            onPageChange={handlePageChange('page_sugestoes')}
            tutelaExterna={cursoTutelado.tipo_tutela === 'externa'}
            canManage={
              can?.uploadCriteriosPap && cursoTutelado.tipo_tutela !== 'externa'
            }
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
