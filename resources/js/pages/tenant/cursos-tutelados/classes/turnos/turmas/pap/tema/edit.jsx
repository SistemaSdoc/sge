import { useForm, usePage } from '@inertiajs/react';

import { store as reenviarTemaColegio } from '@/actions/App/Http/Controllers/Tenant/Colegios/Pap/Aprovacao/Coordenacao/ReenviarTemaPapController';
import { update as atualizarTemaColegio } from '@/actions/App/Http/Controllers/Tenant/Colegios/Pap/TemaPapController';
import { store as reenviarTemaInstituicao } from '@/actions/App/Http/Controllers/Tenant/Pap/Aprovacao/Coordenacao/ReenviarTemaPapController';
import { update as atualizarTemaInstituicao } from '@/actions/App/Http/Controllers/Tenant/Pap/TemaPapController';
import { Button } from '@/components/ui/button';
import { TemaForm } from './components/tema-form';

const STATUS_CONFIG = {
  reprovado: {
    titulo: 'Enviar Nova Proposta de Tema',
    descricao:
      'Defina a nova proposta de tema do grupo e complete os dados para submeter novamente.',
    botaoReenviar: 'Enviar Proposta',
  },
  'melhoria-solicitada': {
    titulo: 'Corrigir Proposta de Tema',
    descricao:
      'Actualize a proposta de acordo com as melhorias solicitadas e reenvie-a para análise.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
  'melhoria-solicitada-tutor': {
    titulo: 'Corrigir Proposta de Tema',
    descricao:
      'Actualize a proposta de acordo com as melhorias solicitadas e reenvie-a para análise.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
  'melhoria-solicitada-coordenacao': {
    titulo: 'Corrigir Proposta de Tema',
    descricao:
      'Actualize a proposta de acordo com as melhorias solicitadas e reenvie-a para análise.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
};

const ROTAS_POR_CONTEXTO = {
  instituicao: {
    atualizar: atualizarTemaInstituicao,
    reenviar: reenviarTemaInstituicao,
  },
  colegio: {
    atualizar: atualizarTemaColegio,
    reenviar: reenviarTemaColegio,
  },
};

export default function Edit() {
  const { grupoPap, routeContext, routeParams, rotaAtualizar, rotaReenviar } =
    usePage().props;
  const statusKey = String(grupoPap?.status_aprovacao || '').toLowerCase();
  const config =
    STATUS_CONFIG[statusKey] ?? STATUS_CONFIG['melhoria-solicitada'];
  const rotas = ROTAS_POR_CONTEXTO[routeContext];
  const urlAtualizar = rotas ? rotas.atualizar.url(routeParams) : rotaAtualizar;
  const urlReenviar = rotas ? rotas.reenviar.url(routeParams) : rotaReenviar;

  const { data, setData, put, post, processing, errors } = useForm({
    tema_grupo: grupoPap?.tema_grupo ?? '',
    problema: grupoPap?.problema ?? '',
    objectivo_geral: grupoPap?.objectivo_geral ?? '',
    objectivos_especificos: grupoPap?.objectivos_especificos ?? '',
    estudo_caso: grupoPap?.estudo_caso ?? '',
  });

  if (!urlAtualizar || !urlReenviar) {
    throw new Error('As rotas para corrigir o tema PAP não foram fornecidas.');
  }

  const atualizar = (event) => {
    event.preventDefault();
    put(urlAtualizar);
  };

  const reenviar = () => {
    post(urlReenviar);
  };

  return (
    <TemaForm
      title={config.titulo}
      description={config.descricao}
      data={data}
      setData={setData}
      errors={errors}
      processing={processing}
      mode="edit"
      showStudyCase={routeContext === 'instituicao'}
      submitFn={atualizar}
      actions={
        <div className="flex flex-col-reverse gap-3 sm:flex-row">
          <Button type="submit" variant="outline" disabled={processing}>
            {processing ? 'A guardar...' : 'Guardar Alterações'}
          </Button>
          <Button type="button" onClick={reenviar} disabled={processing}>
            {processing ? 'A processar...' : config.botaoReenviar}
          </Button>
        </div>
      }
    />
  );
}
