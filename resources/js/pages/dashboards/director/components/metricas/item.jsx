import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { ArrowRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export function MetricItem({ label, value, href }) {
  return (
    <Card className="group cursor-pointer" onClick={() => router.visit(href)}>
      <CardHeader className="p-4">
        <CardDescription className="text-xs">{label}</CardDescription>

        <CardTitle className="text-2xl font-semibold">{value}</CardTitle>

        <CardAction>
          <ArrowRight
            size={20}
            strokeWidth={2}
            className="-rotate-45 text-primary transition-colors duration-300 group-hover:text-muted-foreground"
          />
        </CardAction>
      </CardHeader>
    </Card>
  );
}
