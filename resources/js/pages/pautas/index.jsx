import { Link } from '@inertiajs/react'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { BookOpen, ChevronLeft } from 'lucide-react'
import { EmptyState } from '@/components/empty-state'
import { Button } from '@/components/ui/button'

export default function Index({ turmas = [], cursoTutelado = null }) {
  const isEmpty = !turmas || turmas.length === 0

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <div>
        {cursoTutelado && (
          <Link href={`/instituicoes/${cursoTutelado.id}`}>
            <Button variant="ghost" size="sm" className="mb-2">
              <ChevronLeft className="size-4 mr-1" />
              Voltar
            </Button>
          </Link>
        )}
        <h1 className="text-2xl font-bold">
          {cursoTutelado ? `Pautas — ${cursoTutelado.curso?.nome}` : 'Pautas'}
        </h1>
        <p className="text-sm text-muted-foreground mt-1">
          Selecione uma turma para visualizar a pauta
        </p>
      </div>

      {isEmpty ? (
        <EmptyState
          icon={BookOpen}
          title="Nenhuma pauta disponível"
          description="Não tem acesso a nenhuma turma com pautas"
        />
      ) : (
        <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
          {turmas.map((turma) => (
            <Link
              key={turma.id}
              href={`/instituicoes/${turma.instituicao?.id}/cursos-tutelados/${turma.cursoTuteladoId}/turmas/${turma.id}/pauta`}
            >
              <Card className="h-full hover:shadow-lg transition-shadow cursor-pointer">
                <CardHeader>
                  <CardTitle className="text-lg">{turma.nome} - {turma.classe} -{turma.turno}
                  </CardTitle>
                  <CardDescription>
                    {turma.curso?.nome || 'Sem curso'}
                  </CardDescription>
                </CardHeader>
                <CardContent>
                  <div className="space-y-2">
                    <div className="flex flex-wrap gap-1">
                      {turma.instituicao?.nome && (
                        <Badge variant="secondary">{turma.instituicao.nome}</Badge>
                      )}
                    </div>
                    <p className="text-xs text-muted-foreground">
                      Clique para ver a pauta
                    </p>
                  </div>
                </CardContent>
              </Card>
            </Link>
          ))}
        </div>
      )}
    </div>
  )
}
