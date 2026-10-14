import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

export function DashboardPanel({
  title,
  description,
  children,
  colSpan,
  headerAction,
}) {
  return (
    <div className={colSpan}>
      <Card className="flex h-full flex-col">
        <CardHeader className="border-b">
          <div className="flex items-start justify-between gap-3">
            <div className="min-w-0">
              <CardTitle>{title}</CardTitle>
              <CardDescription>{description}</CardDescription>
            </div>
            {headerAction}
          </div>
        </CardHeader>

        <CardContent className="flex-1 overflow-y-auto pt-4">
          {children}
        </CardContent>
      </Card>
    </div>
  );
}
