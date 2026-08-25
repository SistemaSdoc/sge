// resources/js/pages/certificado/show.jsx
import { Head } from '@inertiajs/react';
import VerificarCard from './components/verificar-card';

export default function Show({ certificado }) {
  return (
    <>
      <Head title="Verificação de Certificado" />
      <div className="mx-auto w-full max-w-5xl px-4 py-6">
        <VerificarCard certificado={certificado} />
      </div>
    </>
  );
}

Show.layout = (page) => page;
