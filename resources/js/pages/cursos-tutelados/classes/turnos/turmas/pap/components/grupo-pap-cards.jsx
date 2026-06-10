"use client"
import { useRouter } from "next/navigation"
import { Button } from "@/components/ui/button"
import { Card, CardAction, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Badge } from "@/components/ui/badge"
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuSeparator, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { MoreHorizontalIcon, GraduationCap, User, Users, ArrowUpRightIcon } from "lucide-react"
import Link from "next/link"

export function GrupoPapCards({ grupos = [], deleteFn, createHref }) {
  const router = useRouter()

  return (
    <div className="space-y-4">
      {createHref && (
        <div className="flex justify-end">
          <Button asChild>
            <Link href={createHref}>Criar grupo</Link>
          </Button>
        </div>
      )}

      {grupos.length > 0 ? (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {grupos.map(grupo => (
            <Card
              key={grupo.id}
              className="hover:cursor-pointer flex flex-col gap-5"
              onClick={() => router.push(`/dashboard/pap/grupos/${grupo.id}`)}
            >
              <CardHeader>
                <CardTitle>{grupo.nome_grupo}</CardTitle>
                <CardDescription>{grupo.tema_grupo}</CardDescription>

                <CardAction>
                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="icon" className="size-7 shrink-0" onClick={(e) => e.stopPropagation()}>
                        <MoreHorizontalIcon size={15} />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem onClick={(e) => {
                        e.stopPropagation()
                        router.push(`/dashboard/pap/grupos/${grupo.id}`)
                      }}>
                        Ver grupo
                      </DropdownMenuItem>
                      <DropdownMenuSeparator />
                      <DropdownMenuItem variant="destructive" onClick={(e) => {
                        e.stopPropagation()
                        deleteFn(grupo.id)
                      }}>
                        Remover
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </CardAction>
              </CardHeader>

              <CardContent className="flex-1 space-y-3">
                {/* Alunos */}
                <div className="space-y-2">
                  <div className="flex items-center gap-1 text-xs text-muted-foreground">
                    <span>Elementos</span>
                  </div>

                  <div className="flex flex-wrap gap-1">
                    {grupo.elementos?.length > 0
                      ? grupo.elementos.map((elemento) => (
                        <Badge
                          key={elemento.id}
                          variant="secondary"
                          asChild
                          onClick={(e) => e.stopPropagation()}
                          className="hover:underline"
                        >
                          <Link href={`/dashboard/alunos/${elemento.id}`}>
                            {elemento.nome?.split(' ').slice(0, 2).join(' ')} <ArrowUpRightIcon size={10} />
                          </Link>
                        </Badge>
                      ))
                      : <span className="text-xs text-muted-foreground">Sem elementos</span>
                    }
                  </div>
                </div>

                {/* Professor e Turma */}
                <div className="grid grid-cols-2 gap-3 text-sm  pt-3">
                  <div className="space-y-1">
                    <div className="flex items-center gap-1 text-xs text-muted-foreground">
                      <span>Tutor</span>
                    </div>
                    <p className="font-medium text-xs truncate">{grupo.professor ?? '—'}</p>
                  </div>
                  <div className="space-y-1">
                    <p className="text-xs text-muted-foreground">Turma</p>
                    <p className="font-medium text-xs">{grupo.turma ?? '—'}</p>
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      ) : (
        <Card>
          <CardContent className="py-12 text-center text-muted-foreground">
            Nenhum grupo PAP criado
          </CardContent>
        </Card>
      )}
    </div>
  )
}