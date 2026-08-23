import { useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { toggleStatus } from '@/actions/App/Http/Controllers/Central/TenantController';
import { Spinner } from '@/components/spinner';
import { getStatusConfig } from '../helpers/status-badge-config';
import TenantProvisioning from './tenant-provisioning';
import { useState } from 'react';

export function AlterarStatusDialog({
  tenant,
  availableTransitions = {},
  onCancel,
  onSuccess,
}) {
  const { data, setData, post, processing, errors } = useForm({
    status: '',
  });

  const [showProvisioning, setShowProvisioning] = useState(false);

  const handleStatusChange = (value) => {
    setData('status', value);
  };

  const handleConfirm = () => {
    if (!data.status) return;

    // Se está a ativar (active ou trial), mostra o progresso
    if (data.status === 'active' || data.status === 'trial') {
      setShowProvisioning(true);
    }

    post(toggleStatus(tenant.id).url, {
      data: { status: data.status },
      onSuccess: () => {
        // Se não foi ativação, fecha logo
        if (data.status !== 'active' && data.status !== 'trial') {
          onSuccess();
        }
      },
    });
  };

  const hasTransitions = Object.keys(availableTransitions).length > 0;

  return (
    <>
      <div className="space-y-6">
        {/* Form */}
        <FieldGroup>
          <div className="grid grid-cols-1 gap-4">
            {/* Status */}
            <Field>
              <FieldLabel htmlFor="status">
                Novo Status <span className="text-red-500">*</span>
              </FieldLabel>

              <Select
                value={data.status}
                onValueChange={handleStatusChange}
                disabled={processing || !hasTransitions || showProvisioning}
              >
                <SelectTrigger id="status">
                  <SelectValue placeholder="Selecione uma opção" />
                </SelectTrigger>

                <SelectContent>
                  {Object.entries(availableTransitions).map(
                    ([statusValue, label]) => {
                      const config = getStatusConfig(statusValue);
                      return (
                        <SelectItem key={statusValue} value={statusValue}>
                          <span className="flex items-center gap-2">
                            <span className={cn('size-1.5', config.dot)} />
                            <span>{label}</span>
                          </span>
                        </SelectItem>
                      );
                    },
                  )}
                </SelectContent>
              </Select>

              {!hasTransitions && (
                <FieldDescription className="">
                  Nenhuma ação disponível para este status.
                </FieldDescription>
              )}

              {errors.status && <FieldError>{errors.status}</FieldError>}
            </Field>
          </div>
        </FieldGroup>

        {/* Botões */}
        <div className="flex justify-end gap-3">
          <Button
            variant="outline"
            onClick={onCancel}
            disabled={processing || showProvisioning}
          >
            Cancelar
          </Button>

          <Button
            onClick={handleConfirm}
            disabled={processing || !data.status || showProvisioning}
          >
            {processing && <Spinner className="mr-2 size-4 animate-spin" />}
            Alterar Status
          </Button>
        </div>
      </div>

      {/* Modal de progresso */}
      <TenantProvisioning
        tenantId={tenant.id}
        isOpen={showProvisioning}
        onClose={() => {
          setShowProvisioning(false);
          onSuccess();
        }}
      />
    </>
  );
}
