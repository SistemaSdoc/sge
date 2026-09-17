import { Head, router } from '@inertiajs/react';
import PrazosAbertos from './components/PrazosAbertos';
import PrazosEncerrados from './components/PrazosEncerrados';
import HistoricoSubmissoes from './components/HistoricoSubmissoes';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';

export default function Index({ prazos_abertos, prazos_encerrados, historico }) {
  const handlePageChange = (page) => {
    router.visit('/dashboard/professor/provas', {
      data: { page },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Submissão de Provas" />
      <div className="w-full space-y-6 p-6">
        <h1 className="text-2xl font-bold text-foreground">Submissão de Provas</h1>

        <Tabs defaultValue="abertos" className="space-y-4">
          <TabsList>
            <TabsTrigger value="abertos">Prazos Abertos</TabsTrigger>
            <TabsTrigger value="encerrados">Prazos Encerrados</TabsTrigger>
            <TabsTrigger value="historico">Histórico</TabsTrigger>
          </TabsList>

          <TabsContent value="abertos" className="space-y-4">
            <h2 className="text-xl font-semibold flex items-center gap-2">Prazos Abertos</h2>
            <PrazosAbertos prazos={prazos_abertos} />
          </TabsContent>

          <TabsContent value="encerrados" className="space-y-4">
            <h2 className="text-xl font-semibold flex items-center gap-2">Prazos Encerrados</h2>
            <PrazosEncerrados prazos={prazos_encerrados} />
          </TabsContent>

          <TabsContent value="historico" className="space-y-4">
            <h2 className="text-xl font-semibold flex items-center gap-2">Meu Histórico de Submissões</h2>
            <HistoricoSubmissoes historico={historico} onPageChange={handlePageChange} />
          </TabsContent>
        </Tabs>
      </div>
    </>
  );
}