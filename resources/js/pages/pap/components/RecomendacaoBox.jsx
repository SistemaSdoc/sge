import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

export default function RecomendacaoBox({
    comentario,
    titulo = 'Recomendação da Instituição Tutora'
}) {
    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-lg">
                    {titulo}
                </CardTitle>
            </CardHeader>

            <CardContent>
                <div className="rounded-lg border bg-yellow-50 p-4">
                    <p className="text-sm text-gray-700">
                        {comentario || 'Nenhuma recomendação informada.'}
                    </p>
                </div>
            </CardContent>
        </Card>
    );
}