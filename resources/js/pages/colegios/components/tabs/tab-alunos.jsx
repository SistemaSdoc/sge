import { router } from "@inertiajs/react"
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/components/ui/card"
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/components/ui/table"
import { Button } from "@/components/ui/button"
import { Minus } from "lucide-react"

export function TabAlunos({ alunos, instituicaoId, cursoTuteladoId, turmaId }) {
    const gerarCertificado = (alunoId) => {
        // Construct the URL directly for the certificate generation
        const certificadoUrl = `/instituicoes/${instituicaoId}/cursos-tutelados/${cursoTuteladoId}/turmas/${turmaId}/alunos/${alunoId}/certificado`;
        
        router.visit(certificadoUrl, {
            method: 'get',
            onSuccess: () => console.log("Certificado gerado com sucesso"),
            onError: () => console.error("Erro ao gerar certificado"),
        })
    }

    return (
        <Card className="gap-0">
            <CardHeader className="border-b">
                <CardTitle>Alunos</CardTitle>
                <CardDescription>Alunos inscritos nesta turma</CardDescription>
            </CardHeader>
            <CardContent className="p-0!">
                <Table>
                    <TableHeader>
                        <TableRow className="bg-muted/72">
                            <TableHead className="px-4">Nome</TableHead>
                            <TableHead>Matrícula</TableHead>
                            <TableHead>Email</TableHead>
                            <TableHead className="px-4 text-right">Acções</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {alunos?.map(aluno => (
                            <TableRow key={aluno.id}>
                                <TableCell className="px-4 font-medium">{aluno.nome}</TableCell>
                                <TableCell>{aluno.matricula ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                <TableCell>{aluno.email ?? <Minus size={15} className="text-muted-foreground" />}</TableCell>
                                <TableCell className="px-4 text-right">
                                    <Button variant="outline" size="sm" onClick={() => gerarCertificado(aluno.id)}>
                                        Gerar Certificado
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </CardContent>
        </Card>
    )
}