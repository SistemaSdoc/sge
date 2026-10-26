import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { cn } from '@/lib/utils';

const ALTURA_MAX = 112; // px

export function FaltasTrimestre({ assiduidade }) {
    const porTri = assiduidade?.por_trimestre ?? [];
    const valores = porTri.filter((v) => v != null);
    const temAlgum = valores.some((v) => v > 0);
    const max = Math.max(1, ...valores);

    const resumo = porTri
        .map((v, i) => `${i + 1}.º trimestre: ${v ?? 'sem dados'}`)
        .join('; ');

    return (
        <Card>
            <CardHeader>
                <CardTitle className="text-sm">Faltas por trimestre</CardTitle>
                <p className="text-xs text-muted-foreground">Total de faltas em cada trimestre</p>
            </CardHeader>
            <CardContent>
                {!temAlgum ? (
                    <div className="flex h-40 items-center justify-center bg-muted text-xs text-muted-foreground">
                        Sem faltas registadas
                    </div>
                ) : (
                    <div
                        role="img"
                        aria-label={`Faltas por trimestre. ${resumo}`}
                        className="grid gap-2"
                        style={{ gridTemplateColumns: `repeat(${porTri.length}, minmax(0, 1fr))` }}
                    >
                        {porTri.map((faltas, i) => {
                            const vazio = faltas == null;
                            const altura = vazio || faltas === 0 ? 0 : Math.max(6, (faltas / max) * ALTURA_MAX);

                            return (
                                <div key={i} className="flex flex-col items-center gap-2">
                                    <div className="flex h-40 w-full flex-col items-center justify-end gap-1 border-b">
                                        <span className={cn('text-xs font-medium', vazio && 'text-muted-foreground')}>
                                            {vazio ? '—' : faltas}
                                        </span>
                                        <div className="w-12 bg-amber-500" style={{ height: altura }} />
                                    </div>
                                    <span className="text-xs text-muted-foreground">{i + 1}.º Trimestre</span>
                                </div>
                            );
                        })}
                    </div>
                )}
            </CardContent>
        </Card>
    );
}