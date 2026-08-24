import { router } from '@inertiajs/react';
import {
  CheckCircle2,
  CircleAlert,
  LoaderCircle,
  Minimize2,
  Terminal,
  X,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import { statusStream } from '@/actions/App/Http/Controllers/Central/TenantController';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
  Item,
  ItemContent,
  ItemGroup,
  ItemMedia,
  ItemTitle,
} from '@/components/ui/item';
import { Progress } from '@/components/ui/progress';

const STEPS = [
  { key: 'iniciando', label: 'tenant registado', threshold: 1 },
  { key: 'base_dados_criada', label: 'base de dados criada', threshold: 25 },
  { key: 'migrations_feitas', label: 'migrations', threshold: 50 },
  { key: 'dados_populados', label: 'seeders', threshold: 75 },
  { key: 'criando_instituicao', label: 'instituição criada', threshold: 85 },
];

const TERMINAL_STATUSES = new Set(['concluido', 'erro']);

function useReducedMotion() {
  const [reducedMotion, setReducedMotion] = useState(false);

  useEffect(() => {
    const mediaQuery = window.matchMedia('(prefers-reduced-motion: reduce)');
    const handleChange = (event) => setReducedMotion(event.matches);

    setReducedMotion(mediaQuery.matches);
    mediaQuery.addEventListener('change', handleChange);

    return () => mediaQuery.removeEventListener('change', handleChange);
  }, []);

  return reducedMotion;
}

export default function TenantProvisioning({ tenantId, isOpen, onClose }) {
  const [progress, setProgress] = useState(null);
  const [isMinimized, setIsMinimized] = useState(false);
  const [isConnected, setIsConnected] = useState(false);
  const [connectionError, setConnectionError] = useState(false);
  const reducedMotion = useReducedMotion();

  useEffect(() => {
    if (!isOpen || !tenantId) {
      return undefined;
    }

    setProgress(null);
    setIsMinimized(false);
    setIsConnected(false);
    setConnectionError(false);

    const eventSource = new EventSource(statusStream({ tenant: tenantId }).url);

    eventSource.onopen = () => {
      setIsConnected(true);
      setConnectionError(false);
    };

    eventSource.onmessage = (event) => {
      try {
        const nextProgress = JSON.parse(event.data);

        setProgress(nextProgress);

        if (TERMINAL_STATUSES.has(nextProgress.status)) {
          eventSource.close();
        }
      } catch {
        setConnectionError(true);
      }
    };

    eventSource.onerror = () => {
      if (eventSource.readyState === EventSource.CLOSED) {
        return;
      }

      setConnectionError(true);
      eventSource.close();
    };

    return () => eventSource.close();
  }, [isOpen, tenantId]);

  if (!isOpen) {
    return null;
  }

  const isFinished = progress?.status === 'concluido';
  const hasError = progress?.status === 'erro' || connectionError;
  const percentage = Math.min(Math.max(progress?.percentagem ?? 0, 0), 100);
  const currentStep = progress?.etapa ?? 'iniciando';
  const currentStepIndex = STEPS.findIndex((step) => step.key === currentStep);
  const activeStep =
    STEPS[currentStepIndex >= 0 ? currentStepIndex : 0] ?? STEPS[0];
  const message = connectionError
    ? 'Não foi possível acompanhar o provisionamento.'
    : (progress?.mensagem ?? 'A ligar ao provisionamento...');

  const handleClose = () => {
    if (!isFinished && !hasError) {
      setIsMinimized(true);
      return;
    }

    onClose();
    router.reload({ only: ['tenants'], preserveScroll: true });
  };

  if (isMinimized) {
    return (
      <Button
        type="button"
        variant="outline"
        size="sm"
        onClick={() => setIsMinimized(false)}
        className="fixed z-50 right-6 bottom-6"
        aria-label="Abrir progresso do provisionamento"
      >
        <LoaderCircle
          className={!isFinished && !hasError ? 'animate-spin' : undefined}
        />
        <Badge
          variant={
            isFinished ? 'success' : hasError ? 'destructive' : 'outline'
          }
        >
          {Math.round(percentage)}%
        </Badge>
        <span>
          {isFinished
            ? 'provisionamento concluído'
            : 'a correr em segundo plano'}
        </span>
      </Button>
    );
  }

  return (
    <Dialog
      open={isOpen && !isMinimized}
      onOpenChange={(open) => {
        if (!open) {
          handleClose();
        }
      }}
    >
      <DialogContent
        showCloseButton={false}
        className="max-w-md p-0 sm:max-w-md"
      >
        <DialogHeader className="flex-row items-center justify-between px-4 py-3 border-b">
          <DialogTitle className="flex items-center gap-2 font-mono text-xs">
            <Terminal size={12} aria-hidden="true" />
            tenant-provisioner
          </DialogTitle>
          <Button
            type="button"
            variant="ghost"
            size="icon-sm"
            onClick={handleClose}
            title={isFinished || hasError ? 'Fechar' : 'Minimizar'}
            aria-label={isFinished || hasError ? 'Fechar' : 'Minimizar'}
          >
            {isFinished || hasError ? (
              <X aria-hidden="true" />
            ) : (
              <Minimize2 aria-hidden="true" />
            )}
          </Button>
        </DialogHeader>

        <div className="flex flex-col gap-5 p-4">
          <div className="flex items-start justify-between gap-4">
            <div>
              <p className="text-xs uppercase">
                {isFinished
                  ? 'Concluído'
                  : hasError
                    ? 'Interrompido'
                    : 'Provisionamento'}
              </p>
              <h3 className="font-semibold">
                {isFinished
                  ? 'Tenant criado com sucesso'
                  : hasError
                    ? 'Ocorreu um erro'
                    : 'A provisionar o novo tenant'}
              </h3>
              <DialogDescription className="sr-only">
                Acompanhe o progresso da criação do tenant.
              </DialogDescription>
            </div>
            <Badge
              variant={
                isFinished ? 'success' : hasError ? 'destructive' : 'outline'
              }
            >
              {Math.round(percentage)}%
            </Badge>
          </div>

          <div className="flex flex-col gap-2">
            <div className="flex items-center justify-between text-xs">
              <span>{activeStep.label}</span>
              <span>{isConnected ? 'ligado' : 'a ligar...'}</span>
            </div>
            <Progress value={percentage} className="h-2" />
          </div>

          <Item variant="muted" size="sm">
            <ItemMedia variant="icon">
              {isFinished ? (
                <CheckCircle2 aria-hidden="true" />
              ) : hasError ? (
                <CircleAlert aria-hidden="true" />
              ) : (
                <LoaderCircle
                  className={reducedMotion ? undefined : 'animate-spin'}
                  aria-hidden="true"
                />
              )}
            </ItemMedia>
            <ItemContent>
              <ItemTitle>{message}</ItemTitle>
            </ItemContent>
            {!isFinished && !hasError && !reducedMotion && (
              <span className="animate-pulse" aria-hidden="true">
                ...
              </span>
            )}
          </Item>

          <ItemGroup className="gap-1">
            {STEPS.map((step, index) => {
              const isDone =
                isFinished ||
                (currentStepIndex >= 0 && index < currentStepIndex);
              const isActive = activeStep.key === step.key && !isDone;

              return (
                <Item key={step.key} variant="outline" size="xs">
                  <ItemMedia variant="icon">
                    {isDone ? (
                      <CheckCircle2 aria-hidden="true" />
                    ) : isActive ? (
                      <LoaderCircle
                        className={reducedMotion ? undefined : 'animate-spin'}
                        aria-hidden="true"
                      />
                    ) : (
                      <span aria-hidden="true">{index + 1}</span>
                    )}
                  </ItemMedia>
                  <ItemContent>
                    <ItemTitle>{step.label}</ItemTitle>
                  </ItemContent>
                </Item>
              );
            })}
          </ItemGroup>

          {hasError && (
            <Item variant="muted" size="sm">
              <ItemMedia variant="icon">
                <CircleAlert aria-hidden="true" />
              </ItemMedia>
              <ItemContent>
                <ItemTitle>{message}</ItemTitle>
              </ItemContent>
            </Item>
          )}

          {isFinished || hasError ? (
            <Button type="button" onClick={handleClose} className="w-full">
              <CheckCircle2 aria-hidden="true" />
              Continuar
            </Button>
          ) : (
            <Button
              type="button"
              variant="ghost"
              onClick={() => setIsMinimized(true)}
              className="w-full"
            >
              <Minimize2 aria-hidden="true" />
              Minimizar e continuar em segundo plano
            </Button>
          )}
        </div>
      </DialogContent>
    </Dialog>
  );
}
