import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export function DashboardPanel({ title, description, children, colSpan }) {
  return (
    <div className={colSpan}>
      <Card className="flex h-full flex-col">
        <CardHeader className="border-b">
          <CardTitle>{title}</CardTitle>
          <CardDescription>{description}</CardDescription>
        </CardHeader>

        <CardContent className="flex-1 overflow-y-auto pt-4">
          {children}
        </CardContent>
      </Card>
    </div>
  );
}
