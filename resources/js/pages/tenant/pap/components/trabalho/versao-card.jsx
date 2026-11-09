import {
  ChevronDown,
  ChevronUp,
  Download,
  Eye,
  AlertCircle,
} from 'lucide-react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { FEEDBACK_CONFIG } from './feedback-config';

export function VersaoCard({
  versao,
  canDownload,
  downloadUrl,
  visualizarUrl,
  getDownloadCorrecaoUrl,
  feedbackCountLabel = 'Comentário(s)',
}) {
  const [expandido, setExpandido] = useState(false);

  return (
    <div className="space-y-3 border bg-card p-4">
      <div className="flex flex-wrap items-center justify-between gap-2">
        <div className="flex items-center gap-3">
          <div className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary/10">
            <span className="text-xs font-semibold text-primary">
              v{versao.numero_versao}
            </span>
          </div>
          <div>
            <p className="text-sm font-medium">{versao.nome_original}</p>
            <p className="text-xs text-muted-foreground">
              {versao.submetido_por} ·{' '}
              {new Date(versao.created_at).toLocaleString('pt-PT', {
                day: '2-digit',
                month: 'short',
                year: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
              })}
            </p>
          </div>
        </div>

        <div className="flex flex-wrap items-center gap-2">
          {canDownload && (
            <Button variant="outline" size="sm" asChild>
              <a href={visualizarUrl} target="_blank" rel="noopener noreferrer">
                <Eye className="size-4" />
                Visualizar
              </a>
            </Button>
          )}

          {canDownload && (
            <Button variant="outline" size="sm" asChild>
              <a href={downloadUrl} target="_blank" rel="noopener noreferrer">
                <Download className="size-4" />
                Download
              </a>
            </Button>
          )}

          {versao.feedbacks?.length > 0 && (
            <Button
              variant="ghost"
              size="sm"
              onClick={() => setExpandido(!expandido)}
            >
              {expandido ? (
                <ChevronUp className="size-4" />
              ) : (
                <ChevronDown className="size-4" />
              )}
              {versao.feedbacks.length} {feedbackCountLabel}
            </Button>
          )}
        </div>
      </div>

      {expandido && versao.feedbacks?.length > 0 && (
        <div className="space-y-2 border-t pt-2">
          {versao.feedbacks.map((feedback) => {
            const config = FEEDBACK_CONFIG[feedback.tipo] ?? {};
            const Icon = config.icon ?? AlertCircle;

            return (
              <div key={feedback.id} className={`border p-3 ${config.bg}`}>
                <div className="mb-1 flex flex-wrap items-center gap-2">
                  <Icon className={`size-4 ${config.color} shrink-0`} />
                  <span className={`text-xs font-medium ${config.color}`}>
                    {config.label}
                  </span>
                  <span className="ml-auto text-xs text-muted-foreground">
                    {feedback.utilizador} ·{' '}
                    {new Date(feedback.created_at).toLocaleString('pt-PT', {
                      day: '2-digit',
                      month: 'short',
                      hour: '2-digit',
                      minute: '2-digit',
                    })}
                  </span>
                </div>
                {feedback.comentario && (
                  <p className="mt-1 text-sm text-foreground/80">
                    {feedback.comentario}
                  </p>
                )}
                {feedback.tem_ficheiro_correcao && (
                  <a
                    href={getDownloadCorrecaoUrl(feedback.id)}
                    target="_blank"
                    rel="noopener noreferrer"
                    className="mt-1 inline-flex items-center gap-1 text-xs text-primary underline-offset-2 hover:underline"
                  >
                    <Download className="size-3" />
                    {feedback.nome_original_correcao ??
                      'Descarregar PDF com correções'}
                  </a>
                )}
              </div>
            );
          })}
        </div>
      )}
    </div>
  );
}
