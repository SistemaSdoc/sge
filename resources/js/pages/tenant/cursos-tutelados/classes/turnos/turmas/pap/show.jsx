import { useState } from 'react';
import { Card } from '@/components/ui/card';
import { usePagination } from '@/hooks/use-pagination';
import { AlertaEstadoTema } from './components/show/alerta-estado-tema';
import { DialogoDataDefesa } from './components/show/dialogo-data-defesa';
import { GrupoPapCabecalho } from './components/show/grupo-pap-cabecalho';
import { GrupoPapResumo } from './components/show/grupo-pap-resumo/grupo-pap-resumo';
import { GrupoPapTabs } from './components/show/grupo-pap-tabs';
import { useDataDefesa } from './hooks/show/use-data-defesa';
import { useGrupoPapActions } from './hooks/show/use-grupo-pap-actions';

export default function Show({
  instituicao,
  cursoTutelado,
  cursoClasse,
  cursoClasseTurno,
  turma,
  grupoPap,
  currentUserId,
  trabalho,
  historico,
  banca,
  elementos,
  can,
  errors: pageErrors = {},
}) {
  const [notas, setNotas] = useState({});
  const bancaPagination = usePagination('banca');
  const elementosPagination = usePagination('elementos');

  const params = {
    instituicao: instituicao.id,
    cursoTutelado: cursoTutelado.id,
    cursoClasse: cursoClasse.id,
    cursoClasseTurno: cursoClasseTurno.id,
    turma: turma.id,
    grupoPap: grupoPap.id,
  };

  const {
    editarGrupo,
    definirTema,
    removerIntegrante,
    removerGrupo,
    removerJurado,
    actualizarNota,
  } = useGrupoPapActions(params);

  const dataDefesa = useDataDefesa(grupoPap, params);

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-4 md:p-6">
      <Card className="overflow-hidden pt-0!">
        <GrupoPapCabecalho
          grupoPap={grupoPap}
          can={can}
          onEdit={editarGrupo}
          onDefinirTema={definirTema}
          onDefinirData={dataDefesa.openDialog}
          onDelete={removerGrupo}
        />
        <GrupoPapResumo
          grupoPap={grupoPap}
          turma={turma}
          currentUserId={currentUserId}
        />
      </Card>

      <DialogoDataDefesa
        open={dataDefesa.open}
        onOpenChange={dataDefesa.handleOpenChange}
        data={dataDefesa.data}
        setData={dataDefesa.setData}
        errors={dataDefesa.errors}
        processing={dataDefesa.processing}
        onSubmit={dataDefesa.submit}
        onCancel={dataDefesa.closeDialog}
      />

      <AlertaEstadoTema grupoPap={grupoPap} can={can} />

      <GrupoPapTabs
        grupoPap={grupoPap}
        params={params}
        can={can}
        notas={notas}
        setNotas={setNotas}
        elementos={elementos}
        banca={banca}
        historico={historico}
        trabalho={trabalho}
        currentUserId={currentUserId}
        pageErrors={pageErrors}
        actualizarNotaFn={actualizarNota}
        removerIntegranteFn={removerIntegrante}
        removerJuradoFn={removerJurado}
        onElementosPageChange={elementosPagination.handlePageChange}
        onBancaPageChange={bancaPagination.handlePageChange}
      />
    </div>
  );
}
