import { router } from '@inertiajs/react';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TabTurmas } from './components/tabs/tab-turmas';
import { TabProfessores } from './components/tabs/tab-professores';
import { TabCriteriosPap } from './components/tabs/tab-criteriospap';
import { Badge } from '@/components/ui/badge';
import { show as showClasse } from '@/actions/App/Http/Controllers/Tenant/CursoClasseController';
import {
  edit,
  show as showCurso,
} from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoProfessorController';
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
// Imports a adicionar:
import { useState, useRef } from 'react';
import { FileText, Upload } from 'lucide-react';
import { uploadCriteriosPap } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export default function Show({
  instituicao,
  cursoTutelado,
  anoLectivoId,
  anosLectivos = [],
  can,
}) {
  const { deleteConfirm } = useDialog();

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
            professore: vinculoId,
          }).url,
        ),
    });
  };

  const handlePageChange = (param) => (page) => {
    router.visit(showCurso({ ...params }).url, {
      data: {
        page_turmas: cursoTutelado.turmas?.current_page ?? 1,
        page_professores: cursoTutelado.professores?.current_page ?? 1,
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
    <div className="mx-auto w-full max-w-6xl space-y-4 p-6">
      <Header can={can} params={params} />

      {/* Tabs */}
      <Tabs defaultValue="turmas" onValueChange={(value) => {}}>
        <div className="flex w-full flex-col gap-3 md:flex md:flex-row md:justify-between">
          <TabsList className="order-2 w-auto md:order-1">
            <TabsTrigger value="turmas" className="hover:cursor-pointer">
              Turmas
            </TabsTrigger>
            <TabsTrigger value="professores" className="hover:cursor-pointer">
              Professores
            </TabsTrigger>
            <TabsTrigger value="criterios-pap" className="hover:cursor-pointer">
              Critérios para a PAP
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
            pagination={cursoTutelado?.professores}
            onPageChange={handlePageChange('page_professores')}
          />
        </TabsContent>

        <TabsContent value="criterios-pap" className="mt-2">
          <TabCriteriosPap
            params={params}
            criteriosPapUrl={cursoTutelado.criterios_pap_url}
            manualPtUrl={cursoTutelado.manual_pt_url}
            estruturaTrabalhoPapUrl={cursoTutelado.estrutura_trabalho_pap_url}
            can={cursoTutelado.can}
          />
        </TabsContent>
      </Tabs>
    </div>
  );
}
