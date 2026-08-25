import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

export default function InfoGrupoBox({
  tema,
  turma: overrideTurma = null,
  cursoTutelado: overrideCurso = null,
  showCurso = true,
  showAlunos = true,
  statusBadge = null,
}) {
  const turma = overrideTurma ?? tema.turma;
  const curso =
    overrideCurso ??
    turma?.cursoClasseTurno?.cursoClasse?.cursoTutelado?.instituicaoCurso
      ?.curso;
  return (
    <Card>
      <CardHeader>
        <div className="flex items-start justify-between gap-4">
          <div>
            <CardTitle>{tema.nome_grupo || `Grupo PAP #${tema.id}`}</CardTitle>

            <p className="mt-1 text-sm text-gray-500">
              Tema: {tema.tema_grupo}
            </p>
          </div>

          {statusBadge && (
            <Badge variant={statusBadge.variant}>{statusBadge.label}</Badge>
          )}
        </div>
      </CardHeader>

      <CardContent>
        <div
          className={`grid gap-3 ${showAlunos ? 'md:grid-cols-2' : 'md:grid-cols-3'}`}
        >
          {/* Turma */}
          <div>
            <p className="text-sm font-medium">Turma</p>
            <p className="text-sm text-gray-600">
              {tema.turma?.nome || 'Não informado'}
            </p>
          </div>

          {/* Curso */}
          {showCurso && (
            <div>
              <p className="text-sm font-medium">Curso</p>
              <p className="text-sm text-gray-600">
                {curso?.nome ||
                  tema.turma?.cursoClasseTurno?.cursoClasse?.cursoTutelado
                    ?.instituicaoCurso?.curso?.nome ||
                  'Não informado'}
              </p>
            </div>
          )}

          {/* Professor Tutor */}
          <div>
            <p className="text-sm font-medium">Professor Tutor</p>
            <p className="text-sm text-gray-600">
              {tema.professor?.user?.nome ||
                tema.professor?.nome ||
                'Não informado'}
            </p>
          </div>

          {/* Alunos */}
          {showAlunos && (
            <div>
              <p className="text-sm font-medium">Alunos</p>
              <p className="text-sm text-gray-600">
                {tema.elementos?.length || 0} integrante(s)
              </p>
            </div>
          )}
        </div>

        {/* Lista de alunos */}
        {showAlunos && tema.elementos?.length > 0 && (
          <div className="mt-5">
            <p className="mb-2 text-sm font-medium">Integrantes do Grupo</p>

            <div className="space-y-1">
              {tema.elementos.map((elemento) => (
                <div
                  key={elemento.id}
                  className="rounded-md bg-gray-50 px-3 py-2 text-sm"
                >
                  {elemento.aluno?.nome || 'Aluno não informado'}
                </div>
              ))}
            </div>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
