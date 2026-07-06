import { GrupoPapCards } from './components/grupo-pap-cards';
import { Head } from '@inertiajs/react';

export default function GrupoPapIndex({ gruposPap = [] }) {
  return (
    <>
      <Head title="Grupos Pap" />
      <GrupoPapCards grupos={gruposPap.data ?? []} deleteFn={() => {}} />
    </>
  );
}
