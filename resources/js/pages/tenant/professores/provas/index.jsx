import { Head, router } from '@inertiajs/react';
import { Clock, History, FolderOpen } from 'lucide-react';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
} from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import PrazosAbertos from './components/PrazosAbertos';
import PrazosEncerrados from './components/PrazosEncerrados';
import HistoricoSubmissoes from './components/HistoricoSubmissoes';

export default function Index({
  prazos_abertos = [],
  prazos_encerrados = [],
  historico,
}) {
  const handlePageChange = (page) => {
    router.visit('/dashboard/professor/provas', {
      data: { page },
      preserveScroll: true,
    });
  };

  const totalAbertos = prazos_abertos.length;
  const totalEncerrados = prazos_encerrados.length;
  const totalHistorico = historico?.data?.length ?? 0;

  return (
    <>
      <Head title="Submissão de Provas" />

      <div className="mx-auto w-full max-w-7xl space-y-4 p-4 sm:p-6">
        <Card className="gap-0 pb-0">
          <CardHeader className="border-b border-foreground/10">
            <div className="min-w-0 space-y-1">
              <Breadcrumb>
                <BreadcrumbList className="flex-wrap">
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbLink asChild>
                      <span className="line-clamp-2 text-sm font-semibold text-primary">
                        Professor
                      </span>
                    </BreadcrumbLink>
                  </BreadcrumbItem>
                  <BreadcrumbSeparator />
                  <BreadcrumbItem className="min-w-0">
                    <BreadcrumbPage className="line-clamp-2 text-sm font-semibold text-secondary">
                      Submissão de Provas
                    </BreadcrumbPage>
                  </BreadcrumbItem>
                </BreadcrumbList>
              </Breadcrumb>
              <CardDescription>
                Submeta as provas para os prazos abertos e acompanhe o estado
                das submissões anteriores.
              </CardDescription>
            </div>
          </CardHeader>

          <CardContent className="p-4">
            <Tabs defaultValue="abertos" className="space-y-4">
              <TabsList className="flex-wrap">
                <TabsTrigger value="abertos" className="gap-2">
                  <FolderOpen className="size-4" />
                  Prazos Abertos
                  {totalAbertos > 0 && (
                    <Badge
                      variant="secondary"
                      className="h-5 min-w-5 rounded-full px-1.5 text-[10px]"
                    >
                      {totalAbertos}
                    </Badge>
                  )}
                </TabsTrigger>
                <TabsTrigger value="encerrados" className="gap-2">
                  <Clock className="size-4" />
                  Encerrados
                  {totalEncerrados > 0 && (
                    <Badge
                      variant="secondary"
                      className="h-5 min-w-5 rounded-full px-1.5 text-[10px]"
                    >
                      {totalEncerrados}
                    </Badge>
                  )}
                </TabsTrigger>
                <TabsTrigger value="historico" className="gap-2">
                  <History className="size-4" />
                  Histórico
                  {totalHistorico > 0 && (
                    <Badge
                      variant="secondary"
                      className="h-5 min-w-5 rounded-full px-1.5 text-[10px]"
                    >
                      {totalHistorico}
                    </Badge>
                  )}
                </TabsTrigger>
              </TabsList>

              <TabsContent value="abertos" className="space-y-3">
                <PrazosAbertos prazos={prazos_abertos} />
              </TabsContent>

              <TabsContent value="encerrados" className="space-y-3">
                <PrazosEncerrados prazos={prazos_encerrados} />
              </TabsContent>

              <TabsContent value="historico" className="space-y-3">
                <HistoricoSubmissoes
                  historico={historico}
                  onPageChange={handlePageChange}
                />
              </TabsContent>
            </Tabs>
          </CardContent>
        </Card>
      </div>
    </>
  );
}