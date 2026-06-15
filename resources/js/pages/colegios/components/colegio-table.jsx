import { router, usePage } from '@inertiajs/react'
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { EmptyState } from "@/components/empty-state"
import { SchoolIcon } from "lucide-react"
import {
    Pagination,
    PaginationContent,
    PaginationItem,
    PaginationNext,
    PaginationPrevious,
} from "@/components/ui/pagination"
import { show } from '@/actions/App/Http/Controllers/CursoTuteladoController'
import { index } from '@/actions/App/Http/Controllers/CursoTuteladoController'

export function ColegioTable({ colegios }) {
    const { instituicao } = usePage().props
    const isEmpty = !colegios?.data || colegios.data.length === 0
    console.log(colegios.data[0])
    return (
        <Card className="gap-0">
            <CardHeader className="border-b">
                <CardTitle>Colégios</CardTitle>
                <CardDescription>Colégios com cursos tutelados</CardDescription>
            </CardHeader>

            <CardContent className="p-0!">
                {isEmpty ? (
                    <EmptyState
                        variant="table"
                        icon={SchoolIcon}
                        title="Nenhum colégio encontrado"
                        description="Não tutela nenhum curso em colégios"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/72">
                                <TableHead className="px-4">Nome</TableHead>
                                <TableHead>Cursos</TableHead>
                            </TableRow>
                        </TableHeader>

                        <TableBody>
                            {colegios.data.map(colegio => (
                                <TableRow
                                    key={colegio.id}
                                    className="hover:cursor-pointer"
                                    onClick={() => router.visit(`/instituicoes/${instituicao.id}/colegios/${colegio.id}`)}
                                >
                                    <TableCell className="px-4 font-medium">{colegio.nome}</TableCell>
                                    <TableCell>{colegio.cursos?.length ?? 0}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>

            {!isEmpty && (
                <CardFooter className="justify-between">
                    <span className="text-muted-foreground">
                        Página {colegios.meta?.current_page} de {colegios.meta?.last_page}
                    </span>

                    <Pagination>
                        <PaginationContent>
                            <PaginationItem>
                                <PaginationPrevious
                                    href={colegios.links?.prev ?? '#'}
                                    onClick={(e) => {
                                        e.preventDefault()
                                        if (colegios.links?.prev) router.visit(colegios.links.prev)
                                    }}
                                />
                            </PaginationItem>
                            <PaginationItem>
                                <PaginationNext
                                    href={colegios.links?.next ?? '#'}
                                    onClick={(e) => {
                                        e.preventDefault()
                                        if (colegios.links?.next) router.visit(colegios.links.next)
                                    }}
                                />
                            </PaginationItem>
                        </PaginationContent>
                    </Pagination>
                </CardFooter>
            )}
        </Card>
    )
}