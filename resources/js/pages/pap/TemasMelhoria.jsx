import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

import InfoGrupoBox from './components/InfoGrupoBox';
import RecomendacaoBox from './components/RecomendacaoBox';

const STATUS_CONFIG = {
    'reprovado': {
        label: 'Reprovado',
        variant: 'destructive',
        botao: 'Enviar Novo Tema',
    },
    'melhoria-solicitada': {
        label: 'Melhoria Solicitada',
        variant: 'outline',
        botao: 'Corrigir Tema',
    },
};

export default function TemasParaCorrigir({
    temas = [],
    rotaEditar,
}) {
    const [loading, setLoading] = useState(null);

    const abrirEdicao = (tema) => {
        setLoading(tema.id);

        router.get(
            rotaEditar.replace(':id', tema.id),
            {},
            { onFinish: () => setLoading(null) }
        );
    };

    return (
        <div className="space-y-6 p-6">
            <div>
                <h1 className="text-3xl font-bold">
                    Temas PAP Pendentes de Correção
                </h1>

                <p className="mt-1 text-gray-600">
                    Consulte o motivo da instituição tutora, corrija ou
                    substitua o tema e reenvie para nova análise.
                </p>
            </div>

            {temas.length === 0 ? (
                <Card>
                    <CardContent className="py-10">
                        <div className="text-center">
                            <p className="text-lg font-medium text-gray-600">
                                Nenhum tema precisa de correção
                            </p>
                            <p className="mt-1 text-sm text-gray-500">
                                Não existem grupos PAP aguardando ação.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-6">
                    {temas.map((tema) => {
                        const config = STATUS_CONFIG[tema.status_aprovacao]
                            ?? STATUS_CONFIG['melhoria-solicitada'];

                        return (
                            <div key={tema.id} className="space-y-4">
                                <InfoGrupoBox
                                    tema={tema}
                                    showCurso={true}
                                    showAlunos={false}
                                    statusBadge={{
                                        label: config.label,
                                        variant: config.variant,
                                    }}
                                />

                                <RecomendacaoBox
                                    comentario={tema.comentario_aprovacao}
                                />

                                <div className="flex justify-end">
                                    <Button
                                        onClick={() => abrirEdicao(tema)}
                                        disabled={loading === tema.id}
                                    >
                                        {loading === tema.id
                                            ? 'A abrir...'
                                            : config.botao}
                                    </Button>
                                </div>
                            </div>
                        );
                    })}
                </div>
            )}
        </div>
    );
}