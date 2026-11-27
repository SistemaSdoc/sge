import { FileText } from 'lucide-react';

export function DocumentoGrupo({ label, url }) {
  if (!url) {
    return null;
  }

  return (
    <div>
      <p className="text-sm text-muted-foreground">{label}</p>
      <a
        href={url}
        target="_blank"
        rel="noopener noreferrer"
        className="flex items-center gap-1.5 font-medium text-primary hover:underline"
      >
        <FileText className="size-4" />
        Ver documento
      </a>
    </div>
  );
}
