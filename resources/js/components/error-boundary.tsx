import { Component, ErrorInfo, ReactNode } from 'react';

interface State {
  error: Error | null;
}

export class ErrorBoundary extends Component<
  { children: ReactNode; fallback?: ReactNode },
  State
> {
  state: State = { error: null };

  static getDerivedStateFromError(error: Error): State {
    return { error };
  }

  componentDidCatch(error: Error, info: ErrorInfo) {
    console.error('💥 ErrorBoundary:', error, info);
  }

  render() {
    if (this.state.error) {
      if (this.props.fallback) {
        return this.props.fallback;
      }

      return (
        <div className="m-4 rounded-lg border border-red-300 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950/20">
          <h2 className="text-lg font-bold text-red-700 dark:text-red-300">
            💥 Erro no layout
          </h2>
          <p className="mt-2 text-sm text-red-600 dark:text-red-400">
            {this.state.error.message}
          </p>
          <details className="mt-3">
            <summary className="cursor-pointer text-xs text-red-500">
              Stack trace (para debug)
            </summary>
            <pre className="mt-1 max-h-64 overflow-auto whitespace-pre-wrap text-[10px] text-red-500/70">
              {this.state.error.stack}
            </pre>
          </details>
        </div>
      );
    }

    return this.props.children;
  }
}