import { useEffect, useState } from 'react';
import { statusStream } from '@/actions/App/Http/Controllers/Central/TenantController';

export default function TenantProvisioning({ tenantId, isOpen, onClose }) {
  const [progresso, setProgresso] = useState(null);

  useEffect(() => {
    if (!isOpen || !tenantId) return;

    setProgresso(null);

    // Abre a ligação SSE com o servidor
    const eventSource = new EventSource(statusStream({ tenant: tenantId }).url);

    eventSource.onmessage = (event) => {
      const dados = JSON.parse(event.data);
      setProgresso(dados);

      // Se terminou, fecha a ligação após 2 segundos
      if (dados.status === 'concluido' || dados.status === 'erro') {
        setTimeout(() => {
          eventSource.close();
        }, 2000);
      }
    };

    eventSource.onerror = () => {
      eventSource.close();
    };

    return () => {
      eventSource.close();
    };
  }, [isOpen, tenantId]);

  if (!isOpen || !progresso) return null;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50">
      <div className="w-full max-w-md rounded-lg bg-white p-8 shadow-xl">
        {/* Título */}
        <h2 className="mb-6 text-xl font-bold text-gray-900">
          A criar tenant...
        </h2>

        {/* Barra de progresso */}
        <div className="mb-6">
          <div className="mb-2 flex justify-between">
            <span className="text-sm font-medium text-gray-700">Progresso</span>
            <span className="text-sm font-bold text-blue-600">
              {progresso.percentagem}%
            </span>
          </div>
          <div className="h-3 w-full overflow-hidden rounded-full bg-gray-200">
            <div
              className="h-3 rounded-full bg-blue-600 transition-all duration-500"
              style={{ width: `${progresso.percentagem}%` }}
            />
          </div>
        </div>

        {/* Mensagem atual */}
        <div className="mb-8 flex items-center gap-3">
          {progresso.status === 'em_progresso' && (
            <div className="animate-spin">
              <svg
                className="h-5 w-5 text-blue-600"
                fill="none"
                stroke="currentColor"
                viewBox="0 0 24 24"
              >
                <circle
                  cx="12"
                  cy="12"
                  r="10"
                  stroke="currentColor"
                  strokeWidth="2"
                  fill="none"
                  opacity="0.3"
                />
                <path
                  d="M12 2a10 10 0 010 20"
                  stroke="currentColor"
                  strokeWidth="2"
                />
              </svg>
            </div>
          )}

          {progresso.status === 'concluido' && (
            <div className="text-green-600">
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
          )}

          {progresso.status === 'erro' && (
            <div className="text-red-600">
              <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path
                  fillRule="evenodd"
                  d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                  clipRule="evenodd"
                />
              </svg>
            </div>
          )}

          <span className="font-medium text-gray-900">
            {progresso.mensagem}
          </span>
        </div>

        {/* Lista de etapas */}
        <div className="mb-6 space-y-2">
          <Etapa
            nome="Base de dados criada"
            completa={[
              'base_dados_criada',
              'migrations_feitas',
              'dados_populados',
              'criando_instituicao',
              'concluido',
            ].includes(progresso.etapa)}
            ativa={progresso.etapa === 'base_dados_criada'}
          />
          <Etapa
            nome="Migrations executadas"
            completa={[
              'migrations_feitas',
              'dados_populados',
              'criando_instituicao',
              'concluido',
            ].includes(progresso.etapa)}
            ativa={progresso.etapa === 'migrations_feitas'}
          />
          <Etapa
            nome="Dados populados"
            completa={[
              'dados_populados',
              'criando_instituicao',
              'concluido',
            ].includes(progresso.etapa)}
            ativa={progresso.etapa === 'dados_populados'}
          />
          <Etapa
            nome="Instituição criada"
            completa={['criando_instituicao', 'concluido'].includes(
              progresso.etapa,
            )}
            ativa={progresso.etapa === 'criando_instituicao'}
          />
        </div>

        {/* Mensagem de erro */}
        {progresso.status === 'erro' && (
          <div className="mb-6 rounded border border-red-200 bg-red-50 p-3">
            <p className="text-sm text-red-700">{progresso.mensagem}</p>
          </div>
        )}

        {/* Botão fechar (só aparece quando termina) */}
        {(progresso.status === 'concluido' || progresso.status === 'erro') && (
          <button
            onClick={() => {
              setProgresso(null);
              onClose();
              window.location.reload();
            }}
            className="w-full rounded-lg bg-blue-600 py-2 font-medium text-white transition hover:bg-blue-700"
          >
            Fechar
          </button>
        )}
      </div>
    </div>
  );
}

/**
 * Componente que mostra uma etapa individual
 */
function Etapa({ nome, completa, ativa }) {
  return (
    <div className="flex items-center gap-3">
      <div
        className={`flex h-5 w-5 shrink-0 items-center justify-center rounded-full border-2 transition ${
          completa
            ? 'border-green-500 bg-green-500'
            : ativa
              ? 'border-blue-600'
              : 'border-gray-300'
        }`}
      >
        {completa && <span className="text-xs font-bold text-white">✓</span>}
        {ativa && !completa && (
          <div className="h-2 w-2 animate-pulse rounded-full bg-blue-600" />
        )}
      </div>
      <span
        className={`text-sm ${
          completa || ativa ? 'font-medium text-gray-900' : 'text-gray-500'
        }`}
      >
        {nome}
      </span>
    </div>
  );
}
