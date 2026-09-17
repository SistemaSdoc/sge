import { Head } from '@inertiajs/react';
import { PersonalData } from './components/personal-data';

export default function Show({ user }) {
  return (
    <>
      <Head title="Meu Perfil" />

      <PersonalData user={user} />
    </>
  );
}