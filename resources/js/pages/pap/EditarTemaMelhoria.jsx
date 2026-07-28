import { router } from '@inertiajs/react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

import InfoGrupoBox from './components/InfoGrupoBox';
import RecomendacaoBox from './components/RecomendacaoBox';

export default function EditarTemaMelhoria({
    grupoPap,
    rotaAtualizar,
    rotaReenviar,
}) {
    const [tema, setTema] = useState(
        grupoPap?.tema_grupo || ''
    );

    const [nomeGrupo, setNomeGrupo] = useState(
        grupoPap?.nome_grupo || ''
    );

    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState({});

    /**
     * Atualizar tema
     */
    const atualizar = () => {
        setLoading(true);

        router.put(
            rotaAtualizar.replace(':id', grupoPap.id),
            {
                nome_grupo: nomeGrupo,
                tema_grupo: tema,
            },
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: (newErrors) => {
                    setErrors(newErrors);
                    setLoading(false);
                },
                onFinish: () => {
                    setLoading(false);
                },
            }
        );
    };

    /**
     * Reenviar para aprovação
     */
    const reenviar = () => {
        setLoading(true);

        router.post(
            rotaReenviar.replace(':id', grupoPap.id),
            {},
            {
                onSuccess: () => {
                    setLoading(false);
                },
                onError: (newErrors) => {
                    setErrors(newErrors);
                    setLoading(false);
                },
                onFinish: () => {
                    setLoading(false);
                },
            }
        );
    };

    return (
        <div className="mx-auto max-w-4xl space-y-6 p-6">

            {/* Cabeçalho */}
            <div>
                <h1 className="text-3xl font-bold">
                    Corrigir Tema PAP
                </h1>

                <p className="mt-1 text-gray-600">
                    Faça as alterações solicitadas pela
                    instituição tutora antes de reenviar.
                </p>
            </div>

            {/* Informações do grupo usando componente reutilizável */}
            <InfoGrupoBox
                tema={grupoPap}
                showCurso={false}
                showAlunos={false}
                statusBadge={{
                    label: 'Melhoria Solicitada',
                    variant: 'outline',
                }}
            />

            {/* Recomendação usando componente reutilizável */}
            <RecomendacaoBox
                comentario={grupoPap.comentario_aprovacao}
            />

            {/* Formulário de correção */}
            <Card>

                <CardHeader>
                    <CardTitle>
                        Corrigir Tema
                    </CardTitle>
                </CardHeader>

                <CardContent className="space-y-5">

                    {/* Nome do grupo */}
                    <div className="space-y-2">

                        <label className="text-sm font-medium">
                            Nome do Grupo
                        </label>

                        <Input
                            value={nomeGrupo}
                            onChange={(e) =>
                                setNomeGrupo(e.target.value)
                            }
                            disabled={loading}
                        />

                        {errors.nome_grupo && (
                            <p className="text-sm text-red-500">
                                {errors.nome_grupo}
                            </p>
                        )}

                    </div>

                    {/* Tema */}
                    <div className="space-y-2">

                        <label className="text-sm font-medium">
                            Tema do Grupo
                        </label>

                        <Textarea
                            value={tema}
                            onChange={(e) =>
                                setTema(e.target.value)
                            }
                            disabled={loading}
                            className="min-h-32"
                        />

                        {errors.tema_grupo && (
                            <p className="text-sm text-red-500">
                                {errors.tema_grupo}
                            </p>
                        )}

                    </div>

                    {/* Ações */}
                    <div className="flex flex-wrap justify-end gap-3">

                        <Button
                            variant="outline"
                            onClick={atualizar}
                            disabled={loading}
                        >
                            {loading
                                ? 'A guardar...'
                                : 'Guardar Alterações'}
                        </Button>

                        <Button
                            onClick={reenviar}
                            disabled={loading}
                        >
                            {loading
                                ? 'A processar...'
                                : 'Corrigir e Reenviar'}
                        </Button>

                    </div>

                </CardContent>

            </Card>

        </div>
    );
}