import { router, useForm } from '@inertiajs/react';
import { useState } from 'react';
import { definirData } from '@/actions/App/Http/Controllers/Tenant/GrupoPapController';

export function useDataDefesa(grupoPap, params) {
  const [open, setOpen] = useState(false);
  const { data, setData, put, processing, errors, reset, setError } = useForm({
    data_defesa: grupoPap?.data_defesa
      ? grupoPap.data_defesa.split('T')[0]
      : '',
    hora_defesa: grupoPap?.data_defesa
      ? grupoPap.data_defesa.split('T')[1]?.slice(0, 5)
      : '',
    local_defesa: grupoPap?.local_defesa ?? '',
  });

  const openDialog = () => {
    setOpen(true);
  };

  const closeDialog = () => {
    setOpen(false);
    reset();
  };

  const handleOpenChange = (isOpen) => {
    setOpen(isOpen);

    if (!isOpen) {
      reset();
    }
  };

  const submit = () => {
    if (!data.data_defesa) {
      setError('data_defesa', 'A data da defesa é obrigatória.');

      return;
    }

    if (!data.hora_defesa) {
      setError('hora_defesa', 'A hora da defesa é obrigatória.');

      return;
    }

    put(definirData.url(params), {
      onSuccess: () => {
        setOpen(false);
        router.reload();
      },
    });
  };

  return {
    open,
    data,
    setData,
    errors,
    processing,
    openDialog,
    closeDialog,
    handleOpenChange,
    submit,
  };
}
