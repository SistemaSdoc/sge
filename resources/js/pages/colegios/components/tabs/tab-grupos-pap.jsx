import { router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Badge } from "@/components/ui/badge"
import { EmptyState } from "@/components/empty-state"
import { Minus, UsersIcon } from "lucide-react"
import { show } from "@/actions/App/Http/Controllers/GrupoPapController"

export function TabGruposPap({ grupos }) {
    const isEmpty = !grupos || grupos.length === 0

    return (
        <Card className="gap-0">
            <CardHeader className="border-b">
                <CardTitle>Grupos PAP</CardTitle>
                <CardDescription>Grupos de Prova de Aptidão Profissional</CardDescription>
            </CardHeader>
            <CardContent className="p-0!">
                {isEmpty ? (
                    <EmptyState
                        variant="table"
                        icon={UsersIcon}
                        title="Nenhum grupo PAP encontrado"
                        description="Ainda não existem grupos PAP nesta turma"
                    />
                ) : (
                    <Table>
                        <TableHeader>
                            <TableRow className="bg-muted/72">
                                <TableHead className="px-4">Nome do Grupo</TableHead>
                                <TableHead>Tema</TableHead>
                                <TableHead>Professor Tutor</TableHead>
                                <TableHead>Nº Elementos</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Nota Final</TableHead>
                                <TableHead>Data Defesa</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {grupos.map(grupo => (
                                <TableRow
                                    key={grupo.id}
                                    className="hover:cursor-pointer"
                                    onClick={() => router.visit(show.url(grupo.id))}
                                >
                                    <TableCell className="px-4 font-medium">{grupo.nome_grupo}</TableCell>
                                    <TableCell>{grupo.tema_grupo ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                    <TableCell>{grupo.professor ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                    <TableCell>{grupo.elementos?.length ?? 0}</TableCell>
                                    <TableCell>
                                        {grupo.status
                                            ? <Badge variant="outline">{grupo.status}</Badge>
                                            : <Minus size={15} className="text-muted-foreground" />
                                        }
                                    </TableCell>
                                    <TableCell>{grupo.nota_final ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                    <TableCell>{grupo.data_defesa ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                )}
            </CardContent>
        </Card>
    )
}