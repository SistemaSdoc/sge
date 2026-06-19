// components/dashboard-card.jsx
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function DashboardPanel({ title, children, colSpan }) {
  return (
    <div className={colSpan}>
      <Card className="flex h-full flex-col">
        <CardHeader className="border-b pb-4">
          <CardTitle className="text-base">{title}</CardTitle>
        </CardHeader>

        <CardContent className="flex-1 overflow-y-auto pt-4">
          {children}
        </CardContent>
      </Card>
    </div>
  );
}
