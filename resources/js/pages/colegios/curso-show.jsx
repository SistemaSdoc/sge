import { Head, usePage } from '@inertiajs/react'
import { Tabs, TabsContent, TabsList, TabsTrigger } from "@/components/ui/tabs"
import { TabAlunos } from "./components/tabs/tab-alunos"
import { TabPauta } from "./components/tabs/tab-pauta"
import { TabGruposPap } from "./components/tabs/tab-grupos-pap"

export default function CursoTuteladoShow() {
    const { auth, cursoTutelado } = usePage().props

    return (
        <>
            <Head title={cursoTutelado.curso} />
            <div className="w-full max-w-7xl mx-auto space-y-6">
                <div>
                    <h1 className="text-2xl font-semibold">{cursoTutelado.curso}</h1>
                    <p className="text-muted-foreground">{cursoTutelado.colegio?.nome}</p>
                </div>

                {cursoTutelado.classes?.map(classe => (
                    <div key={classe.id} className="space-y-4">
                        <h2 className="text-lg font-medium">{classe.nome}</h2>

                        {classe.turnos?.map(turno => (
                            <div key={turno.id} className="space-y-2">
                                <h3 className="text-sm font-medium text-muted-foreground">{turno.nome}</h3>

                                {turno.turmas?.map(turma => (
                                    <div key={turma.id} className="space-y-4">
                                        <h4 className="text-sm font-medium">Turma: {turma.nome}</h4>

                                        <Tabs defaultValue="alunos">
                                            <TabsList>
                                                <TabsTrigger value="alunos">Alunos</TabsTrigger>
                                                <TabsTrigger value="pauta">Pauta</TabsTrigger>
                                                <TabsTrigger value="grupos-pap">Grupos PAP</TabsTrigger>
                                            </TabsList>

                                            <TabsContent value="alunos">
                                                <TabAlunos
                                                    alunos={turma.alunos}
                                                    instituicaoId={auth.user.instituicao_id}
                                                    cursoTuteladoId={cursoTutelado.id}
                                                    turmaId={turma.id}
                                                />
                                            </TabsContent>

                                            <TabsContent value="pauta">
                                                <TabPauta
                                                    instituicaoId={auth.user.instituicao_id}
                                                    cursoTuteladoId={cursoTutelado.id}
                                                    turmaId={turma.id}
                                                />
                                            </TabsContent>

                                            <TabsContent value="grupos-pap">
                                                <TabGruposPap
                                                    grupos={turma.grupos_pap}
                                                    colegioId={cursoTutelado.colegio?.id}
                                                    cursoTuteladoId={cursoTutelado.id}
                                                />
                                            </TabsContent>
                                        </Tabs>
                                    </div>
                                ))}
                            </div>
                        ))}
                    </div>
                ))}
            </div>
        </>
    )
}