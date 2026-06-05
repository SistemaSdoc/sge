import { Button } from "@/components/ui/button";
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
import { Skeleton } from "@/components/ui/skeleton";

export default function IndexSkeleton() {
  return (
    <Card className="gap-0">
      <CardHeader className="border-b">
        <CardTitle>Instituições</CardTitle>
        <CardDescription>Lista de intituições</CardDescription>
        <CardAction>
          <Button disabled>Criar</Button>
        </CardAction>
      </CardHeader>
      <CardContent className="p-0!">
        <Table>
          <TableHeader>
            <TableRow className="bg-muted/72">
              <TableHead className="px-4">Sigla</TableHead>
              <TableHead>Nome</TableHead>
              <TableHead>Tipo</TableHead>
              <TableHead className="px-4 text-right">Acções</TableHead>
            </TableRow>
          </TableHeader>

          <TableBody>
            {Array(5).fill(0).map((_, idx) => (
              <TableRow key={idx}>
                <TableCell className="px-4 font-medium">
                  <Skeleton className="w-20.75 h-3.5" />
                </TableCell>

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
  )
}