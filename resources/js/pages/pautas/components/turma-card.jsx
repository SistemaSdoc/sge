import { Link } from "@inertiajs/react";
import {
  Card,
  CardAction,
  CardContent,
  CardHeader,
  CardTitle,
} from "@/components/ui/card";
import { Badge } from "@/components/ui/badge";

export function TurmaCard({ turmas = [] }) {
  return (
    <div className="grid md:grid-cols-4 grid-cols-1 gap-4">
      {turmas?.map((turma) => (
        <Link key={turma.id} href={`/pautas/${turma.id}`}>
          <Card>
            <CardHeader>
              <CardTitle>{turma.nome}</CardTitle>

              <CardAction>
                <Badge>{turma?.classe?.nome}</Badge>
              </CardAction>
            </CardHeader>

            <CardContent className="space-y-3 pt-0">
              {/* Curso */}
              <div className="flex items-center gap-2 text-sm">
                <span className="font-medium text-foreground line-clamp-1">
                  {turma?.curso?.nome}
                </span>
              </div>

              {/* Turno */}
              <div className="flex items-center justify-between gap-6 text-sm">
                <span>{turma?.turno?.nome}</span>
              </div>
            </CardContent>
          </Card>
        </Link>
      ))}
    </div>
  );
}
