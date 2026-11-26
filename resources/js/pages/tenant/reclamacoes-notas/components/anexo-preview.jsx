import { Download, Eye } from 'lucide-react';
import { Button } from '@/components/ui/button';

const previewableTypes = ['application/pdf', 'image/jpeg', 'image/png'];

export function AnexoPreview({ anexo, previewUrl, downloadUrl }) {
  const supportsPreview = previewableTypes.includes(anexo.mime_type);

  return (
    <div className="flex min-w-0 flex-wrap items-center gap-2">
      <span className="max-w-64 truncate text-sm" title={anexo.nome_original}>
        {anexo.nome_original}
      </span>

      {supportsPreview && (
        <Button asChild variant="outline" size="sm">
          <a href={previewUrl} target="_blank" rel="noopener noreferrer">
            <Eye aria-hidden="true" />
            Visualizar
          </a>
        </Button>
      )}

      <Button asChild variant="outline" size="sm">
        <a href={downloadUrl}>
          <Download aria-hidden="true" />
          Baixar
        </a>
      </Button>

      {!supportsPreview && (
        <span className="text-xs text-muted-foreground">
          Pré-visualização indisponível neste formato.
        </span>
      )}
    </div>
  );
}
