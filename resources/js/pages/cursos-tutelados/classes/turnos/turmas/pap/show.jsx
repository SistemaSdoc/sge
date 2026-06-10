'use client';
import { useState } from 'react';
import { useRouter } from 'next/navigation';
import { Button } from '@/components/ui/button';
import { useGrupoPap } from '../hooks/useGrupoPap';
import { TabBanca } from '../components/tabs/tab-banca';
import { Card, CardContent } from '@/components/ui/card';
import { useRemoverJurado } from '../hooks/useRemoverJurado';
import { useDeleteGrupoPap } from '../hooks/useDeleteGrupoPap';
import { useActualizarNota } from '../hooks/useActualizarNota';
import { Loader2, Minus, MoreHorizontalIcon } from 'lucide-react';
import { TabIntegrantes } from '../components/tabs/tab-integrantes';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import Link from 'next/link';

export function GrupoPapShow({ id }) {
  const router = useRouter();
  const { data, isLoading } = useGrupoPap(id);
  const { mutate: deleteGrupo } = useDeleteGrupoPap();
  const { mutate: removerJurado } = useRemoverJurado(id);
  const { mutate: actualizarNota } = useActualizarNota(id);
  const [notas, setNotas] = useState({});

  if (isLoading)
    return (
      <div className="flex justify-center py-20">
        <Loader2 className="size-8 animate-spin" />
      </div>
    );

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6">
      {/* Header */}
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />
          <div className="relative z-10 flex w-full items-end justify-between p-6">
            <div className="space-y-2 text-white">
              <h1 className="text-2xl font-semibold md:text-3xl">
                {data?.nome_grupo}
              </h1>
              <p className="text-sm opacity-90">{data?.tema_grupo}</p>
            </div>
            <DropdownMenu>
              <DropdownMenuTrigger asChild>
                <Button
                  variant="ghost"
                  size="icon"
                  className="text-white hover:bg-white/20"
                >
                  <MoreHorizontalIcon />
                </Button>
              </DropdownMenuTrigger>
              <DropdownMenuContent align="end" className="w-full max-w-4">
                <DropdownMenuItem
                  onClick={() =>
                    router.push(
                      `/dashboard/pap/grupos/${id}/data-defesa/create`,
                    )
                  }
                >
                  Definir data da defesa
                </DropdownMenuItem>
                <DropdownMenuItem onClick={() => router.push(`#`)}>
                  Editar
                </DropdownMenuItem>
                <DropdownMenuItem
                  variant="destructive"
                  onClick={() =>
                    deleteGrupo(id, {
                      onSuccess: () => router.push('/dashboard/pap/grupos'),
                    })
                  }
                >
                  Remover grupo
                </DropdownMenuItem>
              </DropdownMenuContent>
            </DropdownMenu>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
          <div>
            <p className="text-sm text-muted-foreground">Professor tutor</p>
            <p className="font-medium">
              {data?.professor ? (
                <Link
                  href={`/dashboard/professores/${data?.professor?.id}`}
                  className="hover:underline"
                >
                  {data?.professor?.nome}
                </Link>
              ) : (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Turma</p>
            <p className="font-medium">
              {data?.turma ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Status</p>
            <p className="font-medium">
              {data?.status ?? (
                <Minus size={15} className="text-muted-foreground" />
              )}
            </p>
          </div>
          <div>
            <p className="text-sm text-muted-foreground">Data de defesa</p>
            <p className="font-medium">
              {data?.data_defesa ?? 'Por definir...'}
            </p>
          </div>
        </CardContent>
      </Card>

      <Tabs defaultValue="integrantes-grupo" className="w-full">
        <TabsList>
          <TabsTrigger value="integrantes-grupo">
            Integrantes do grupo
          </TabsTrigger>
          <TabsTrigger value="integrantes-banca">
            Integrantes da banca
          </TabsTrigger>
        </TabsList>

        <TabsContent value="integrantes-grupo">
          <TabIntegrantes
            id={id}
            data={data}
            setNotas={setNotas}
            notas={notas}
            deleteFn={removerJurado}
            actualizarNotaFn={actualizarNota}
          />
        </TabsContent>

        <TabsContent value="integrantes-banca">
          <TabBanca id={id} data={data} removerJuradoFn={removerJurado} />
        </TabsContent>
      </Tabs>
    </div>
  );
}
