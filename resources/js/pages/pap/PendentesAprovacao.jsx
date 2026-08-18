import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Card, CardContent } from '@/components/ui/card';
import { Button } from '@/components/ui/button';

import InfoGrupoBox from './components/InfoGrupoBox';
import ModalDecisaoAprovacao from './components/ModalDecisaoAprovacao';

export default function PendentesAprovacao({
  temasPendentes = [],
  rotaAprovar,
  rotaReprovar,
  rotaMelhoria,
}) {
  const [selectedTema, setSelectedTema] = useState(null);
  const [action, setAction] = useState(null);
  const [comentario, setComentario] = useState('');
  const [loading, setLoading] = useState(false);

  /**
   * Abrir modal de decisão
   */
  const abrirModal = (tema, tipo) => {
    setSelectedTema(tema);
    setAction(tipo);
    setComentario('');
  };

  /**
   * Fechar modal
   */
  const fecharModal = () => {
    if (loading) return;

    setSelectedTema(null);
    setAction(null);
    setComentario('');
  };

  /**
   * Aprovar tema
   */
  const handleAprovar = () => {
    if (!selectedTema) return;

    setLoading(true);

    router.post(
      rotaAprovar.replace(':id', selectedTema.id),
      {
        comentario: comentario || null,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          fecharModal();
          setLoading(false);
        },
        onError: () => {
          setLoading(false);
        },
      },
    );
  };

  /**
   * Reprovar tema
   */
  const handleReprovar = () => {
    if (!selectedTema) return;

    if (comentario.trim().length < 10) {
      return;
    }

    setLoading(true);

    router.post(
      rotaReprovar.replace(':id', selectedTema.id),
      {
        motivo: comentario,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          fecharModal();
          setLoading(false);
        },
        onError: () => {
          setLoading(false);
        },
      },
    );
  };

  /**
   * Solicitar melhoria
   */
  const handleMelhoria = () => {
    if (!selectedTema) return;

    if (comentario.trim().length < 10) {
      return;
    }

    setLoading(true);

    router.post(
      // ← POST, não GET!
      rotaMelhoria.replace(':id', selectedTema.id),
      {
        recomendacao: comentario,
      },
      {
        preserveScroll: true,
        onSuccess: () => {
          fecharModal();
          setLoading(false);
        },
        onError: () => {
          setLoading(false);
        },
      },
    );
  };

  /**
   * Confirmar ação selecionada
   */
  const confirmarAcao = () => {
    if (action === 'aprovar') {
      handleAprovar();
    }

    if (action === 'reprovar') {
      handleReprovar();
    }

    if (action === 'melhoria') {
      handleMelhoria();
    }
  };

  return (
    <div className="space-y-6 p-6">
      {/* Cabeçalho */}
      <div>
        <h1 className="text-3xl font-bold">Temas PAP Pendentes</h1>

        <p className="mt-1 text-gray-600">
          Analise os temas dos grupos PAP dos cursos que coordena.
        </p>
      </div>

      {/* Nenhum tema */}
      {temasPendentes.length === 0 ? (
        <Card>
          <CardContent className="py-10">
            <div className="text-center">
              <p className="text-lg font-medium text-gray-600">
                Nenhum tema pendente
              </p>

              <p className="mt-1 text-sm text-gray-500">
                Não existem temas PAP aguardando análise.
              </p>
            </div>
          </CardContent>
        </Card>
      ) : (
        <div className="space-y-4">
          {temasPendentes.map((tema) => (
            <div key={tema.id}>
              {/* Info do grupo usando componente reutilizável */}
              <InfoGrupoBox
                tema={tema}
                showCurso={true}
                showAlunos={true}
                statusBadge={{
                  label: 'Pendente',
                  variant: 'outline',
                }}
              />

              {/* Botões de ação */}
              <div className="mt-4 flex flex-wrap justify-end gap-2">
                <Button onClick={() => abrirModal(tema, 'aprovar')}>
                  Aprovar
                </Button>

                <Button
                  variant="destructive"
                  onClick={() => abrirModal(tema, 'reprovar')}
                >
                  Reprovar
                </Button>

                <Button
                  variant="outline"
                  onClick={() => abrirModal(tema, 'melhoria')}
                >
                  Solicitar Melhoria
                </Button>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Modal de decisão usando componente reutilizável */}
      <ModalDecisaoAprovacao
        open={!!selectedTema}
        onClose={fecharModal}
        tema={selectedTema}
        action={action}
        comentario={comentario}
        onComentarioChange={setComentario}
        onConfirmar={confirmarAcao}
        loading={loading}
      />
    </div>
  );
}
