import { router } from '@inertiajs/react';

import { Badge } from '@/components/ui/badge';

import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function HistoricoAprovacao({ grupoPap, historico = [] }) {
  const getStatusLabel = (status) => {
    switch (status) {
      case 'pendente':
        return 'Pendente';

      case 'aprovado':
        return 'Aprovado';

      case 'reprovado':
        return 'Reprovado';

      case 'melhoria-solicitada':
        return 'Melhoria Solicitada';

      default:
        return status;
    }
  };

  const getStatusVariant = (status) => {
    switch (status) {
      case 'aprovado':
        return 'default';

      case 'reprovado':
        return 'destructive';

      case 'melhoria-solicitada':
        return 'outline';

      default:
        return 'secondary';
    }
  };

  return (
    <div className="mx-auto max-w-4xl space-y-6 p-6">
      {/* Cabeçalho */}
      <div>
        <h1 className="text-3xl font-bold">Histórico de Aprovação</h1>

        <p className="mt-1 text-gray-600">
          Acompanhe todas as decisões tomadas sobre este tema PAP.
        </p>
      </div>

      {/* Dados do grupo */}
      <Card>
        <CardHeader>
          <CardTitle>{grupoPap.nome_grupo}</CardTitle>
        </CardHeader>

        <CardContent>
          <div className="space-y-2">
            <p>
              <strong>Tema:</strong> {grupoPap.tema_grupo}
            </p>

            <p>
              <strong>Curso:</strong>{' '}
              {grupoPap.turma?.cursoClasseTurno?.cursoClasse?.cursoTutelado
                ?.nome || 'Não informado'}
            </p>

            <p>
              <strong>Professor tutor:</strong>{' '}
              {grupoPap.professor?.user?.nome || 'Não informado'}
            </p>

            <p>
              <strong>Turma:</strong> {grupoPap.turma?.nome || 'Não informado'}
            </p>

            <p>
              <strong>Estado atual:</strong>{' '}
              <Badge variant={getStatusVariant(grupoPap.status_aprovacao)}>
                {getStatusLabel(grupoPap.status_aprovacao)}
              </Badge>
            </p>
          </div>
        </CardContent>
      </Card>

      {/* Histórico */}
      <Card>
        <CardHeader>
          <CardTitle>Histórico de Decisões</CardTitle>
        </CardHeader>

        <CardContent>
          {historico.length === 0 ? (
            <p className="text-sm text-gray-500">
              Nenhum histórico disponível.
            </p>
          ) : (
            <div className="space-y-6">
              {historico.map((item) => (
                <div key={item.id} className="relative border-l-2 pl-5">
                  {/* Estado */}
                  <div className="flex items-center gap-2">
                    <Badge variant={getStatusVariant(item.estado_novo)}>
                      {getStatusLabel(item.estado_novo)}
                    </Badge>
                  </div>

                  {/* Utilizador */}
                  <p className="mt-2 text-sm">
                    <strong>Responsável:</strong>{' '}
                    {item.utilizador?.nome || 'Utilizador não informado'}
                  </p>

                  {/* Data */}
                  <p className="text-xs text-gray-500">
                    {item.created_at
                      ? new Date(item.created_at).toLocaleString('pt-PT')
                      : ''}
                  </p>

                  {/* Comentário */}
                  {item.comentario && (
                    <div className="mt-3 rounded-md bg-gray-50 p-3">
                      <p className="text-sm text-gray-700">{item.comentario}</p>
                    </div>
                  )}
                </div>
              ))}
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
