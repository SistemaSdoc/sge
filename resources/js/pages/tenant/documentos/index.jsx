import { Head, usePage } from '@inertiajs/react';
import { DocumentoTable } from './components/documento-table';

export default function Index() {
  const { documentos, classes } = usePage().props;

  return (
    <div className="mx-auto w-full max-w-7xl p-6">
      <Head title="Documentos" />

      <DocumentoTable
        documentos={documentos ?? []}
        classes={classes ?? []}
      />
    </div>
  );
}