import { Head } from '@inertiajs/react';
import { AcademicData } from './components/academic-data';

export default function Academic({ user, academicData }) {
  return (
    <>
      <Head title="Dados académicos" />

      <AcademicData user={user} data={academicData} />
    </>
  );
}