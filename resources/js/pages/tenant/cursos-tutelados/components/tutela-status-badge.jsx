import { getTutelaStatusConfig } from '../helpers/tutela-status-config';

export function TutelaStatusBadge({ status }) {
  const config = getTutelaStatusConfig(status);

  return (
    <span
      className={`inline-flex items-center gap-1.5 px-2.5 py-1 text-xs font-medium ${config.text}`}
    >
      <span className="relative flex size-1.5">
        {config.pulse && (
          <span
            className={`absolute inline-flex h-full w-full animate-ping ${config.dot} opacity-75`}
          />
        )}
        <span className={`relative inline-flex size-1.5 ${config.dot}`} />
      </span>
      {config.label}
    </span>
  );
}
