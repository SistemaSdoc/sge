"use client"

import { Button } from "@/components/ui/button";
import { Card, CardAction, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu";
import { MoreHorizontalIcon, Minus, Users2Icon } from "lucide-react";
import Link from "next/link";
import { EmptyState } from "@/components/empty-state";

export function TabBanca({ id, data, removerJuradoFn }) {
  const isEmpty = !data?.banca || data.banca.length === 0

  return (
    <Card className="gap-0 pb-0">
      <CardHeader className="border-b">
        <CardTitle>Integrantes da banca</CardTitle>
        <CardDescription>Professores avaliadores e funções</CardDescription>
        <CardAction>
          <Button asChild>
            <Link href={`/dashboard/pap/grupos/${id}/banca/create`}>Adicionar</Link>
          </Button>
        </CardAction>
      </CardHeader>

      <CardContent className="p-0!">
        {isEmpty ? (
          <EmptyState
            variant="table"
            icon={Users2Icon}
            title="Nenhum membro da banca"
            description="Comece adicionando os jurados para a defesa do grupo PAP"
            action={{
              label: "Adicionar juri",
              href: `/dashboard/pap/grupos/${id}/banca/create`,
              variant: "outline"
            }}
          />
        ) : (
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Nome</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Função</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {data.banca.map(j => (
                <TableRow key={j.id}>
                  <TableCell className="px-4 font-medium">{j.nome}</TableCell>
                  <TableCell>{j.email ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                  <TableCell className="capitalize">{j.funcao}</TableCell>
                  <TableCell className="px-4 text-right">
                    <DropdownMenu>
                      <DropdownMenuTrigger asChild>
                        <Button variant="ghost" size="icon" className="size-8">
                          <MoreHorizontalIcon />
                        </Button>
                      </DropdownMenuTrigger>
                      <DropdownMenuContent align="end">
                        <DropdownMenuItem variant="destructive" onClick={() => removerJuradoFn(j.id)}>
                          Remover da banca
                        </DropdownMenuItem>
                      </DropdownMenuContent>
                    </DropdownMenu>
                  </TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        )}
      </CardContent>
    </Card>
  )
}