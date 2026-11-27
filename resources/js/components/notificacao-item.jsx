import { Link } from '@inertiajs/react';
import { Badge } from '@/components/ui/badge';
import { cn } from '@/lib/utils';

/**
 * Renderiza UMA notificação em formato legível.
 *
 * Tolera 3 estruturas do backend:
 *   A) { titulo, mensagem, dados: {...} }        ← NotificacaoController extrai
 *   B) { data: { titulo, intro, linhas, ... } }  ← Laravel directo
 *   C) { data: { mensagem: "..." } }             ← formato antigo
 */
export function NotificacaoItem({ notificacao, onClick, variant = 'full' }) {
  // ─── Normalizar de onde vêm os campos ───
  const raw = notificacao.data ?? notificacao.dados ?? {};

  // Se o backend já extraiu `titulo`/`mensagem` para o topo,
  // prioriza o topo — senão usa o que está dentro do `raw`.
  const data = {
    ...raw,
    titulo: notificacao.titulo ?? raw.titulo ?? null,
    mensagem: notificacao.mensagem ?? raw.mensagem ?? null,
  };

  const lida = notificacao.lida ?? !!notificacao.read_at;

  // ─────────────────────────────────────────────────────
  // Formato 1 — FORMAL (prazo de provas)
  // ─────────────────────────────────────────────────────
  const isFormal = Boolean(
    data.titulo && (data.intro || data.linhas || data.diretrizes || data.fecho),
  );

  if (isFormal) {
    const {
      titulo,
      intro,
      linhas = [],
      diretrizes,
      fecho,
      assinatura,
      tipo,
      url,
    } = data;

    if (variant === 'compact') {
      return (
        <ItemWrap
          notificacao={notificacao}
          lida={lida}
          url={url}
          onClick={onClick}
        >
          <div className="flex min-w-0 items-start gap-2">
            <div className="min-w-0 flex-1">
              <p className="truncate text-sm font-medium">{titulo}</p>
              <p className="mt-0.5 line-clamp-2 text-xs text-muted-foreground">
                {intro ?? linhas[0] ?? ''}
              </p>
            </div>
            <span className="shrink-0 text-[10px] text-muted-foreground">
              {notificacao.criada_em}
            </span>
            {!lida && <span className="mt-1 size-2 shrink-0 bg-destructive" />}
          </div>
        </ItemWrap>
      );
    }

    return (
      <ItemWrap
        notificacao={notificacao}
        lida={lida}
        url={url}
        onClick={onClick}
        block
      >
        <div className="space-y-3">
          <div className="flex flex-wrap items-center gap-2">
            <h3 className="text-base leading-snug font-semibold">{titulo}</h3>
            <Badge variant="outline" className="text-[10px]">
              {badgeLabel(tipo)}
            </Badge>
            {!lida && (
              <Badge variant="secondary" className="text-[10px]">
                Nova
              </Badge>
            )}
          </div>

          {intro && (
            <p className="text-sm leading-relaxed text-muted-foreground">
              {intro}
            </p>
          )}

          {linhas.length > 0 && (
            <ul className="space-y-1 border-l-2 border-muted pl-3">
              {linhas.map((linha, i) => (
                <li key={i} className="text-sm">
                  {linha}
                </li>
              ))}
            </ul>
          )}

          {diretrizes && (
            <div className="border bg-muted/40 p-3">
              <p className="text-[10px] font-semibold tracking-wide text-muted-foreground uppercase">
                Diretrizes da Direção
              </p>
              <p className="mt-1 text-sm leading-relaxed whitespace-pre-line">
                {diretrizes}
              </p>
            </div>
          )}

          {fecho && <p className="text-sm leading-relaxed">{fecho}</p>}

          {assinatura && (
            <p className="border-t pt-3 text-xs whitespace-pre-line text-muted-foreground">
              {assinatura}
            </p>
          )}
        </div>
      </ItemWrap>
    );
  }

  // ─────────────────────────────────────────────────────
  // Formato 2 — SIMPLES (submissao_avaliada, propina_atraso)
  // ─────────────────────────────────────────────────────
  if (data.titulo || data.mensagem) {
    const titulo = data.titulo ?? null;
    const mensagem = data.mensagem ?? null;
    const url = data.url ?? null;

    if (variant === 'compact') {
      return (
        <ItemWrap
          notificacao={notificacao}
          lida={lida}
          url={url}
          onClick={onClick}
        >
          <div className="flex min-w-0 items-start gap-2">
            <div className="min-w-0 flex-1">
              {titulo && (
                <p className="truncate text-sm font-medium">{titulo}</p>
              )}
              {mensagem && (
                <p className="mt-0.5 line-clamp-2 text-xs whitespace-pre-line text-muted-foreground">
                  {mensagem}
                </p>
              )}
            </div>
            <span className="shrink-0 text-[10px] text-muted-foreground">
              {notificacao.criada_em}
            </span>
            {!lida && <span className="mt-1 size-2 shrink-0 bg-destructive" />}
          </div>
        </ItemWrap>
      );
    }

    return (
      <ItemWrap
        notificacao={notificacao}
        lida={lida}
        url={url}
        onClick={onClick}
        block
      >
        <div className="space-y-2">
          <div className="flex flex-wrap items-center gap-2">
            {titulo && (
              <h3 className="text-base leading-snug font-semibold">{titulo}</h3>
            )}
            {!lida && (
              <Badge variant="secondary" className="text-[10px]">
                Nova
              </Badge>
            )}
          </div>
          {mensagem && (
            <p className="text-sm leading-relaxed whitespace-pre-line text-muted-foreground">
              {mensagem}
            </p>
          )}
        </div>
      </ItemWrap>
    );
  }

  // ─────────────────────────────────────────────────────
  // Formato 3 — Fallback REAL (mostra o que temos)
  // ─────────────────────────────────────────────────────
  return (
    <ItemWrap notificacao={notificacao} lida={lida} onClick={onClick} block>
      <div className="space-y-2">
        <p className="text-sm font-medium">
          {notificacao.tipo ?? 'Notificação'}
        </p>
        <p className="text-xs text-muted-foreground">
          Conteúdo indisponível — verifique o detalhe.
        </p>
      </div>
    </ItemWrap>
  );
}

// ─────────────────────────────────────────────────────
// Wrapper comum
// ─────────────────────────────────────────────────────
function ItemWrap({ notificacao, lida, url, onClick, block, children }) {
  const className = cn(
    'text-left transition-colors hover:bg-muted/30',
    block ? 'block px-4 py-4' : 'block px-4 py-3',
    lida ? 'opacity-60' : 'bg-muted/10',
  );

  if (url) {
    return (
      <Link href={url} onClick={onClick} className={className}>
        {children}
      </Link>
    );
  }

  return (
    <button type="button" onClick={onClick} className={cn('w-full', className)}>
      {children}
    </button>
  );
}

function badgeLabel(tipo) {
  return (
    {
      criado: 'Novo prazo',
      prorrogado: 'Prorrogado',
      fechado: 'Encerrado',
      a_expirar: 'A expirar',
      expirado: 'Expirado',
    }[tipo] ?? 'Comunicado'
  );
}
