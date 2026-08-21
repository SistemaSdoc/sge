import { useForm, router, usePage } from '@inertiajs/react';
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

export function AlterarStatusDialog({ tenant, onCancel, onSuccess }) {
  const { props } = usePage();
  const statuses = props.statuses || [];

  const { data, setData, post, processing, errors } = useForm({
    status: tenant.status,
  });

  const handleStatusChange = (value) => {
    setData('status', value);
  };

  const handleConfirm = () => {
    post(toggleStatus(tenant.id).url, {
      data: { status: data.status },
      onSuccess: () => {
        onSuccess();
      },
    });
  };

  return (
    <div className="space-y-6">
      {/* Form */}
      <FieldGroup>
        <div className="grid grid-cols-1 gap-4">
          {/* Status */}
          <Field>
            <FieldLabel htmlFor="status">
              Status <span className="text-red-500">*</span>
            </FieldLabel>

            <Select
              value={data.status}
              onValueChange={handleStatusChange}
              disabled={processing}
            >
              <SelectTrigger id="status">
                <SelectValue>
                  {(() => {
                    const config = getStatusConfig(data.status);
                    return (
                      <span className="flex items-center gap-2">
                        <span className={cn('size-1.5', config.dot)} />
                        <span>{config.label}</span>
                      </span>
                    );
                  })()}
                </SelectValue>
              </SelectTrigger>

              <SelectContent>
                {statuses.map((status) => {
                  const config = getStatusConfig(status.value);
                  return (
                    <SelectItem key={status.value} value={status.value}>
                      <span className="flex items-center gap-2">
                        <span className={cn('size-1.5', config.dot)} />
                        <span>{status.label}</span>
                      </span>
                    </SelectItem>
                  );
                })}
              </SelectContent>
            </Select>
            {errors.status && <FieldError>{errors.status}</FieldError>}
            <FieldDescription>Selecione uma opção</FieldDescription>
          </Field>
        </div>
      </FieldGroup>

      {/* Botões */}
      <div className="flex justify-end gap-3">
        <Button variant="outline" onClick={onCancel} disabled={processing}>
          Cancelar
        </Button>

        <Button onClick={handleConfirm} disabled={processing}>
          {processing && <Spinner className="mr-2 size-4 animate-spin" />}
          Alterar Status
        </Button>
      </div>
    </div>
  );
}
