import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export function ReadOnlySection({ title, children }) {
  return (
    <Card>
      <CardHeader>
        <CardTitle>{title}</CardTitle>
      </CardHeader>
      
      <CardContent className="grid gap-6 md:grid-cols-2">
        {children}
      </CardContent>
    </Card>
  );
}
