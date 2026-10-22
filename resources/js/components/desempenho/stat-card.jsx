import {
  Card,
  CardAction,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export default function StatCard({ icon: Icon, label, valor }) {
  return (
    <Card size="sm">
      <CardHeader>
        <CardDescription>{label}</CardDescription>
        <CardTitle className="text-2xl">{valor}</CardTitle>
        {Icon && (
          <CardAction>
            <div className="flex size-9 items-center justify-center bg-muted text-muted-foreground">
              <Icon className="size-4" />
            </div>
          </CardAction>
        )}
      </CardHeader>
    </Card>
  );
}
