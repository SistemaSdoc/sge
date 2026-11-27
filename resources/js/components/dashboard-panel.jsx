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
          <div className="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
              <CardTitle>{title}</CardTitle>
              <CardDescription>{description}</CardDescription>
            </div>
            {headerAction && <div className="shrink-0">{headerAction}</div>}
          </div>
        </CardHeader>

        <CardContent className="flex-1 overflow-y-auto pt-4">
          {children}
        </CardContent>
      </Card>
    </div>
  );
}
