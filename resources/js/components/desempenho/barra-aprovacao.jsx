export default function BarraAprovacao({ valor }) {
    const cor =
        valor >= 70 ? 'bg-green-600' : valor >= 50 ? 'bg-yellow-500' : 'bg-destructive';

    return (
        <div className="flex items-center gap-2">
            <div className="h-1.5 w-24 overflow-hidden bg-muted">
                <div className={`h-full ${cor}`} style={{ width: `${Math.min(valor, 100)}%` }} />
            </div>
            <span className="font-medium">{valor}%</span>
        </div>
    );
}