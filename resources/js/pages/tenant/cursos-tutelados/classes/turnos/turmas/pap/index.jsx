import { router } from '@inertiajs/react';
import { GrupoPapCards } from './components/grupo-pap-cards';
import { destroy } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';

export default function Index({ gruposPap = [], can, params = {} }) {
  const deleteGrupoFn = (grupoPap) => {
    router.delete(destroy.url({ ...params, grupoPap }), {
      onSuccess: () => router.reload(),
    });
  };

  return (
    <GrupoPapCards
      params={params}
      grupos={gruposPap}
      deleteGrupoFn={deleteGrupoFn}
      can={can}
    />
  );
}
