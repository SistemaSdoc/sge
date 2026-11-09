import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { TabAprovacao } from './tabs/tab-aprovacao';
import { TabBanca } from './tabs/tab-banca';
import { TabHistorico } from './tabs/tab-historico';
import { TabIntegrantes } from './tabs/tab-integrantes';
import { TabTrabalho } from './tabs/tab-trabalho/tab-trabalho';

export function GrupoPapTabs({
  grupoPap,
  params,
  can,
  notas,
  setNotas,
  elementos,
  banca,
  historico,
  trabalho,
  currentUserId,
  pageErrors,
  actualizarNotaFn,
  removerIntegranteFn,
  removerJuradoFn,
  onElementosPageChange,
  onBancaPageChange,
}) {
  return (
    <Tabs defaultValue="integrantes-grupo" className="w-full">
      <TabsList>
        <TabsTrigger value="integrantes-grupo">
          Integrantes do grupo
        </TabsTrigger>

        {can?.verBanca && (
          <TabsTrigger value="integrantes-banca">
            Integrantes da banca
          </TabsTrigger>
        )}

        {grupoPap.status_aprovacao !== 'rascunho' && (
          <TabsTrigger value="aprovacao">Aprovação do tema</TabsTrigger>
        )}

        {grupoPap.status_aprovacao === 'aprovado' && (
          <TabsTrigger value="trabalho">Trabalho PAP</TabsTrigger>
        )}

        <TabsTrigger value="historico">Histórico</TabsTrigger>
      </TabsList>

      <TabsContent value="integrantes-grupo">
        <TabIntegrantes
          grupoPap={grupoPap}
          params={params}
          setNotas={setNotas}
          notas={notas}
          pagination={elementos}
          onPageChange={onElementosPageChange}
          actualizarNotaFn={actualizarNotaFn}
          removerIntegranteFn={removerIntegranteFn}
          can={can}
          currentUserId={currentUserId}
        />
      </TabsContent>

      {can?.verBanca && (
        <TabsContent value="integrantes-banca">
          <TabBanca
            params={params}
            grupoPap={grupoPap}
            removerJuradoFn={removerJuradoFn}
            pagination={banca}
            onPageChange={onBancaPageChange}
            can={can}
            currentUserId={currentUserId}
          />
        </TabsContent>
      )}
      {grupoPap.status_aprovacao !== 'rascunho' && (
        <TabsContent value="aprovacao">
          <TabAprovacao grupoPap={grupoPap} params={params} can={can} />
        </TabsContent>
      )}

      {grupoPap.status_aprovacao === 'aprovado' && (
        <TabsContent value="trabalho">
          <TabTrabalho
            params={params}
            trabalho={trabalho}
            can={can}
            errors={pageErrors}
          />
        </TabsContent>
      )}

      <TabsContent value="historico">
        <TabHistorico
          params={params}
          grupoPap={grupoPap}
          historico={historico}
        />
      </TabsContent>
    </Tabs>
  );
}
