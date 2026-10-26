import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { ArrowRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export function MetricItem({ label, value, href, disabled = false }) {
  return (
    <Card
      aria-disabled={disabled}
      title={disabled ? 'Não tem permissão para abrir esta lista' : undefined}
      className={`group transition-all duration-200 ${disabled ? 'cursor-not-allowed opacity-60' : 'cursor-pointer hover:bg-muted/50'}`}
      onClick={disabled ? undefined : () => router.visit(href)}
    >
      <CardHeader className="p-4">
        <CardDescription className="text-xs">{label}</CardDescription>

        <CardTitle className="text-2xl font-semibold">{value}</CardTitle>

        <CardAction>
          <ArrowRight
            size={20}
            strokeWidth={2}
            className="-rotate-45 text-secondary transition-colors duration-300 group-hover:text-muted-foreground"
          />
        </CardAction>
      </CardHeader>
    </Card>
  );
}
