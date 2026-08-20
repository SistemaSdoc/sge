import { Button } from "@/components/ui/button"
import { Skeleton } from "@/components/ui/skeleton"
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/components/ui/table"
import {
  Pagination,
  PaginationContent,
  PaginationItem,
  PaginationNext,
  PaginationPrevious,
} from "@/components/ui/pagination";

export default function ShowSkeleton() {
  return (
    <div className="w-full max-w-6xl mx-auto space-y-6">
      {/* Header Skeleton */}
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex items-end w-full h-56 bg-muted overflow-hidden">
          <Skeleton className="absolute inset-0 w-full h-full" />

          <div className="absolute inset-0 z-10 bg-black/50" />

          <div className="relative z-20 flex items-end justify-between w-full p-6">
            <div className="space-y-2 w-2/3">
              <Skeleton className="h-8 w-96" />
              <Skeleton className="h-4 w-72" />
            </div>

            <Button variant="ghost" size="icon" className="" disabled>
              <Skeleton className="w-6 h-6" />
            </Button>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
          <div>
            <p className="text-sm text-muted-foreground mb-2">Telefone</p>
            <Skeleton className="h-4 w-24" />
          </div>

          <div>
            <p className="text-sm text-muted-foreground mb-2">Endereço</p>
            <Skeleton className="h-4 w-28" />
          </div>

          <div>
            <p className="text-sm text-muted-foreground mb-2">Cidade</p>
            <Skeleton className="h-4 w-28" />
          </div>
        </CardContent>
      </Card>

      {/* Cursos/Related Skeleton */}
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Cursos</CardTitle>
          <CardDescription>Cursos lecionados por esta instituição</CardDescription>
          <CardAction>
            <Button disabled>Criar</Button>
          </CardAction>
        </CardHeader>
        <CardContent className="p-0!">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Tutelado por</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>

            <TableBody>
              {Array(5).fill(0).map((_, idx) => (
                <TableRow key={idx}>
                  <TableCell>
                    <Skeleton className="h-4 w-81.75" />
                  </TableCell>

                  <TableCell>
                    <Skeleton className="w-2/3 h-4" />
                  </TableCell>

                  <TableCell className="px-4 text-right">
                    <Button variant="ghost" size="icon" className="size-8">
                      <Skeleton className="w-2/3 h-4" />
                    </Button>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>

        <CardFooter className="justify-between">
          <span className="text-muted-foreground">Página 1 de 4</span>

          <Pagination>
            <PaginationContent>
              <PaginationItem>
                <PaginationPrevious href="#" />
              </PaginationItem>

              <PaginationItem>
                <PaginationNext href="#" />
              </PaginationItem>
            </PaginationContent>
          </Pagination>
        </CardFooter>
      </Card>
    </div>
  )
}