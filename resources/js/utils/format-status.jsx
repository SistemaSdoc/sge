import { Badge } from "@/components/ui/badge";

export function formatStatusInscricao(status) {
    if (status === "aprovado") {
        return <Badge className="bg-green-50 text-green-700">Aprovado</Badge>
    }

    if (status === "pendente") {
        return <Badge className="bg-yellow-50 text-yellow-500">Pendente</Badge>
    }

    if (status === "reprovado") {
        return <Badge className="bg-destructive-foreground/5 text-destructive">Reprovado</Badge>
    }
}