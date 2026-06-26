import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { ArrowRight } from 'lucide-react';
import { router } from '@inertiajs/react';

export function ResumoCardItem({ }) {
  return (
    <Card className="group cursor-pointer">
      <CardHeader className="p-4">
        <CardDescription className="text-xs">Label</CardDescription>

        <CardTitle className="text-2xl font-semibold">13</CardTitle>

        <CardAction>
         
        </CardAction>
      </CardHeader>
    </Card>
  );
}
