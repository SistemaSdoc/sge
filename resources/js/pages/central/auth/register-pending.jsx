import { Head } from '@inertiajs/react';

export default function RegisterPending() {
  return (
    <>
      <Head title="Registo Pendente" />

      <div className="text-center flex flex-col gap-4">
        <h2 className="text-lg font-semibold">Pedido enviado com sucesso!</h2>
        <p className="text-sm text-muted-foreground">
          A sua instituição foi registada e está a aguardar activação pelo administrador.
          Receberá um email assim que a conta for activada.
        </p>
      </div>
    </>
  );
}

RegisterPending.layout = {
  title: 'Aguarda Activação',
  description: 'O seu pedido de registo foi recebido com sucesso.',
};