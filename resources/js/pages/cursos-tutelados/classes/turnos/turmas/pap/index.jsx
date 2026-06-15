import { GrupoPapCards } from './components/grupo-pap-cards';
import { destroy } from '@/actions/App/Http/Controllers/GrupoPapController';

export default function Index({ gruposPap = [] }) {
  const deleteGrupoFn = (grupoPap) => {
    router.delete(destroy.url({ ...params, grupoPap }), {
      onSuccess: () => router.reload(),
    });
  };

  return <GrupoPapCards grupos={gruposPap} deleteGrupoFn={deleteGrupoFn} />;
}
