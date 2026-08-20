import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import InfoGrupoBox from './components/InfoGrupoBox';
import RecomendacaoBox from './components/RecomendacaoBox';

const STATUS_CONFIG = {
  reprovado: {
    label: 'Reprovado',
    variant: 'destructive',
    titulo: 'Enviar Novo Tema PAP',
    descricao:
      'O tema anterior foi reprovado. Defina um novo tema e reenvie para análise.',
    botaoReenviar: 'Enviar Novo Tema',
  },
  'melhoria-solicitada': {
    label: 'Melhoria Solicitada',
    variant: 'outline',
    titulo: 'Corrigir Tema PAP',
    descricao:
      'Faça as alterações solicitadas pela instituição tutora antes de reenviar.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
};

export default function EditarTemaMelhoria({
  grupoPap,
  historico = [],
  rotaAtualizar,
  rotaReenviar,
}) {
  const config =
    STATUS_CONFIG[grupoPap.status_aprovacao] ??
    STATUS_CONFIG['melhoria-solicitada'];

  const [tema, setTema] = useState(grupoPap?.tema_grupo || '');
  const [nomeGrupo, setNomeGrupo] = useState(grupoPap?.nome_grupo || '');
  const [problema, setProblema] = useState(grupoPap?.problema || '');
  const [objectivos, setObjectivos] = useState(grupoPap?.objectivos || '');
  const [loading, setLoading] = useState(false);
  const [errors, setErrors] = useState({});

  const atualizar = () => {
    setLoading(true);

    router.put(
      rotaAtualizar.replace(':id', grupoPap.id),
      { nome_grupo: nomeGrupo, tema_grupo: tema, problema, objectivos }, // envia os valores actuais do form
      {
        onError: setErrors,
        onFinish: () => setLoading(false),
      },
    );
  };

  const reenviar = () => {
    setLoading(true);

    router.put(
      rotaReenviar.replace(':id', grupoPap.id),
      { nome_grupo: nomeGrupo, tema_grupo: tema, problema, objectivos }, // envia os valores actuais do form
      {
        onError: setErrors,
        onFinish: () => setLoading(false),
      },
    );
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6 p-6">
      <div>
        <h1 className="text-3xl font-bold">{config.titulo}</h1>
        <p className="mt-1 text-gray-600">{config.descricao}</p>
      </div>

      <InfoGrupoBox
        tema={grupoPap}
        showCurso={false}
        showAlunos={false}
        statusBadge={{ label: config.label, variant: config.variant }}
      />

      {/* Feed completo do histórico, mais recente primeiro */}
      {historico.length > 0 && (
        <div className="space-y-3">
          <h2 className="text-sm font-medium text-gray-600">
            Histórico de análise
          </h2>

          {historico.map((item) => (
            <RecomendacaoBox
              key={item.id}
              comentario={item.comentario}
              autor={item.utilizador?.name}
              data={item.created_at}
              estado={item.estado_novo}
            />
          ))}
        </div>
      )}

      <Card>
        <CardHeader>
          <CardTitle>{config.titulo}</CardTitle>
        </CardHeader>

        <CardContent className="space-y-5">
          <div className="space-y-2">
            <label className="text-sm font-medium">Nome do Grupo</label>
            <Input
              value={nomeGrupo}
              onChange={(e) => setNomeGrupo(e.target.value)}
              disabled={loading}
            />
            {errors.nome_grupo && (
              <p className="text-sm text-red-500">{errors.nome_grupo}</p>
            )}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Tema do Grupo</label>
            <Input
              value={tema}
              onChange={(e) => setTema(e.target.value)}
              disabled={loading}
            />
            {errors.tema_grupo && (
              <p className="text-sm text-red-500">{errors.tema_grupo}</p>
            )}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Problema</label>
            <Textarea
              value={problema}
              onChange={(e) => setProblema(e.target.value)}
              disabled={loading}
              className="min-h-32"
            />
            {errors.problema && (
              <p className="text-sm text-red-500">{errors.problema}</p>
            )}
          </div>

          <div className="space-y-2">
            <label className="text-sm font-medium">Objectivos</label>
            <Textarea
              value={objectivos}
              onChange={(e) => setObjectivos(e.target.value)}
              disabled={loading}
              className="min-h-32"
            />
            {errors.objectivos && (
              <p className="text-sm text-red-500">{errors.objectivos}</p>
            )}
          </div>

          <div className="flex flex-wrap justify-end gap-3">
            <Button variant="outline" onClick={atualizar} disabled={loading}>
              {loading ? 'A guardar...' : 'Guardar Alterações'}
            </Button>

            <Button onClick={reenviar} disabled={loading}>
              {loading ? 'A processar...' : config.botaoReenviar}
            </Button>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}
