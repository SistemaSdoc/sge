import { Head } from '@inertiajs/react';
import { AcademicData } from './components/academic-data';

export default function Academic() {
  return (
    <>
      <Head title="Dados académicos" />

      <AcademicData />
    </>
  );
}