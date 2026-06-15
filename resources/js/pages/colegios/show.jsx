import { Head, usePage, router } from '@inertiajs/react'
import { Card, CardContent, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { alunos } from '@/actions/App/Http/Controllers/CursoTuteladoController'

export default function Show() {
    const { colegio, instituicao } = usePage().props

    return (
        <>
            <Head title={colegio.nome} />
            <div className="w-full max-w-7xl mx-auto space-y-6">
                <h1 className="text-2xl font-semibold">{colegio.nome}</h1>
                <Card className="gap-0">
                    <CardHeader className="border-b">
                        <CardTitle>Cursos Tutelados</CardTitle>
                    </CardHeader>
                    <CardContent className="p-0!">
                        <Table>
                            <TableHeader>
                                <TableRow className="bg-muted/72">
                                    <TableHead className="px-4">Nome do Curso</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {colegio.cursos?.map(curso => (
                                    <TableRow
                                        key={curso.id}
                                        className="hover:cursor-pointer"
                                        onClick={() => router.visit(alunos.url({ instituicao: instituicao.id, cursoTutelado: curso.curso_tutelado_id }))}
                                    >
                                        <TableCell className="px-4 font-medium">{curso.nome}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>
            </div>
        </>
    )
}