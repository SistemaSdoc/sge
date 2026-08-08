import { router } from '@inertiajs/react';
import { AlertTriangleIcon } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

export function HistoricoPendenteAlert({ aluno, pendentes = [] }) {
    const classes = pendentes.map((p) => p.classe).join(', ');

    return (
        <Card className="border-amber-500 bg-amber-50 dark:bg-amber-950/20">
            <CardContent className="flex items-start gap-3 p-4">
                <AlertTriangleIcon className="mt-0.5 size-5 shrink-0 text-amber-500" />
                <div className="flex-1 space-y-2">
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-400">
                        Histórico escolar incompleto
                    </p>
                    <p className="text-sm text-amber-700 dark:text-amber-500">
                        Este aluno não tem as notas registadas das seguintes classes anteriores:{' '}
                        <strong>{classes}</strong>.
                        Para emitir o certificado correctamente, é necessário lançar o histórico.
                    </p>
                    <Button
                        size="sm"
                        variant="outline"
                        className="border-amber-500 text-amber-700 hover:bg-amber-100 dark:hover:bg-amber-900/30"
                        onClick={() =>
                            router.visit(`/dashboard/alunos/${aluno.id}/historico`)
                        }
                    >
                        Lançar histórico em falta
                    </Button>
                </div>
            </CardContent>
        </Card>
    );
}