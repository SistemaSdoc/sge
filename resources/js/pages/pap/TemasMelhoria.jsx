import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

import InfoGrupoBox from './components/InfoGrupoBox';
import RecomendacaoBox from './components/RecomendacaoBox';

export default function TemasMelhoria({
    temas = [],
    rotaEditar,
}) {
    const [loading, setLoading] = useState(null);

    const abrirEdicao = (tema) => {
        setLoading(tema.id);

        router.get(
            rotaEditar.replace(':id', tema.id),
            {},
            {
                onFinish: () => setLoading(null),
            }
        );
    };

    return (
        <div className="space-y-6 p-6">

            {/* Cabeçalho */}
            <div>
                <h1 className="text-3xl font-bold">
                    Temas PAP com Melhorias Solicitadas
                </h1>

                <p className="mt-1 text-gray-600">
                    Consulte as recomendações da instituição tutora,
                    corrija os temas e reenvie-os para nova análise.
                </p>
            </div>

            {/* Nenhum tema */}
            {temas.length === 0 ? (
                <Card>
                    <CardContent className="py-10">
                        <div className="text-center">
                            <p className="text-lg font-medium text-gray-600">
                                Nenhum tema precisa de melhoria
                            </p>

                            <p className="mt-1 text-sm text-gray-500">
                                Não existem grupos PAP aguardando correção.
                            </p>
                        </div>
                    </CardContent>
                </Card>
            ) : (
                <div className="space-y-6">

                    {temas.map((tema) => (
                        <div key={tema.id} className="space-y-4">

                            {/* Info do grupo usando componente reutilizável */}
                            <InfoGrupoBox
                                tema={tema}
                                showCurso={true}
                                showAlunos={false}
                                statusBadge={{
                                    label: 'Melhoria Solicitada',
                                    variant: 'outline',
                                }}
                            />

                            {/* Recomendação usando componente reutilizável */}
                            <RecomendacaoBox
                                comentario={tema.comentario_aprovacao}
                            />

                            {/* Botão */}
                            <div className="flex justify-end">

                                <Button
                                    onClick={() =>
                                        abrirEdicao(tema)
                                    }
                                    disabled={loading === tema.id}
                                >
                                    {loading === tema.id
                                        ? 'A abrir...'
                                        : 'Corrigir Tema'}
                                </Button>

                            </div>

                        </div>
                    ))}

                </div>
            )}

        </div>
    );
}