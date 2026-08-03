import { GrupoPapCards } from './components/grupo-pap-cards';
import { Head, router } from '@inertiajs/react';
import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Select,
  SelectContent,
  SelectGroup,
  SelectItem,
  SelectLabel,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function GrupoPapIndex({
  gruposPap = [],
  anosLectivos = [],
  anoLectivoId,
}) {
  const handleAnoLectivoChange = (value) => {
    router.visit(window.location.pathname, {
      data: { ano_lectivo_id: value },
      preserveScroll: true,
    });
  };

  return (
    <>
      <Head title="Grupos Pap" />

      <div className="mx-auto w-full max-w-7xl space-y-6 p-6">
        <div className="flex flex-col items-start justify-between gap-4 sm:flex-row sm:items-center">
          <div>
            <h1 className="text-xl font-bold">Grupos PAP</h1>
            <p className="text-muted-foreground">
              Selecione um grupo para visualizar os detalhes e gerir
            </p>
          </div>
          <Select
            value={anoLectivoId ?? ''}
            onValueChange={handleAnoLectivoChange}
          >
            <SelectTrigger id="ano-lectivo" className="w-48">
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

        <GrupoPapCards grupos={gruposPap.data ?? []} deleteFn={() => {}} />
      </div>
    </>
  );
}
