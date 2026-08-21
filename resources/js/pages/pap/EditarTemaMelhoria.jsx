import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

import InfoGrupoBox from './components/InfoGrupoBox';
import RecomendacaoBox from './components/RecomendacaoBox';

const STATUS_CONFIG = {
  reprovado: {
    label: 'Reprovado',
    variant: 'destructive',
    titulo: 'Enviar Novo Tema PAP',
    descricao: 'O tema anterior foi reprovado. Defina um novo tema e reenvie para análise.',
    botaoReenviar: 'Enviar Novo Tema',
  },
  'melhoria-solicitada': {
    label: 'Melhoria Solicitada',
    variant: 'outline',
    titulo: 'Corrigir Tema PAP',
    descricao: 'Faça as alterações solicitadas pela instituição tutora antes de reenviar.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
  'melhoria-solicitada-tutor': {
    label: 'Melhoria Solicitada',
    variant: 'outline',
    titulo: 'Corrigir Tema PAP',
    descricao: 'Faça as alterações solicitadas pelo professor tutor antes de reenviar.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
  'melhoria-solicitada-coordenacao': {
    label: 'Melhoria Solicitada',
    variant: 'outline',
    titulo: 'Corrigir Tema PAP',
    descricao: 'Faça as alterações solicitadas pela coordenação antes de reenviar.',
    botaoReenviar: 'Corrigir e Reenviar',
  },
};

export default function EditarTemaMelhoria({
  grupoPap,
  historico = [],
  rotaAtualizar,
  rotaReenviar,
}) {
  const statusKey = String(grupoPap?.status_aprovacao || '').toLowerCase();
  const config = STATUS_CONFIG[statusKey] ?? STATUS_CONFIG['melhoria-solicitada'];

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
      { nome_grupo: nomeGrupo, tema_grupo: tema, problema, objectivos },
      { onError: setErrors, onFinish: () => setLoading(false) },
    );
  };

  const reenviar = () => {
    setLoading(true);
    router.put(
      rotaReenviar.replace(':id', grupoPap.id),
      { nome_grupo: nomeGrupo, tema_grupo: tema, problema, objectivos },
      { onError: setErrors, onFinish: () => setLoading(false) },
    );
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6">
      {/* Cabeçalho */}
      <div>
        <h1 className="text-2xl font-semibold tracking-tight">{config.titulo}</h1>
        <p className="mt-1 text-sm text-muted-foreground">{config.descricao}</p>
      </div>

      <InfoGrupoBox
        tema={grupoPap}
        showCurso={false}
        showAlunos={false}
        statusBadge={{ label: config.label, variant: config.variant }}
      />

      {/* Histórico */}
      {historico.length > 0 && (
        <div className="space-y-3">
          <p className="text-sm font-medium text-muted-foreground">Histórico de análise</p>
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

      {/* Formulário */}
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>{config.titulo}</CardTitle>
        </CardHeader>

        <CardContent className="space-y-5 pt-6">
          <div className="space-y-1.5">
            <Label htmlFor="nome-grupo">Nome do Grupo</Label>
            <Input
              id="nome-grupo"
              value={nomeGrupo}
              onChange={(e) => setNomeGrupo(e.target.value)}
              disabled={loading}
            />
            {errors.nome_grupo && (
              <p className="text-xs text-red-500">{errors.nome_grupo}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="tema-grupo">Tema do Grupo</Label>
            <Input
              id="tema-grupo"
              value={tema}
              onChange={(e) => setTema(e.target.value)}
              disabled={loading}
            />
            {errors.tema_grupo && (
              <p className="text-xs text-red-500">{errors.tema_grupo}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="problema">Problema</Label>
            <Textarea
              id="problema"
              value={problema}
              onChange={(e) => setProblema(e.target.value)}
              disabled={loading}
              className="min-h-32"
            />
            {errors.problema && (
              <p className="text-xs text-red-500">{errors.problema}</p>
            )}
          </div>

          <div className="space-y-1.5">
            <Label htmlFor="objectivos">Objectivos</Label>
            <Textarea
              id="objectivos"
              value={objectivos}
              onChange={(e) => setObjectivos(e.target.value)}
              disabled={loading}
              className="min-h-32"
            />
            {errors.objectivos && (
              <p className="text-xs text-red-500">{errors.objectivos}</p>
            )}
          </div>

          <div className="flex flex-wrap justify-end gap-3 border-t pt-4">
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