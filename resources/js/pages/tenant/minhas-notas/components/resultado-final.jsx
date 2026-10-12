import { Badge } from '@/components/ui/badge';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

const RESULTADOS = {
  transita: 'Transitou',
  transita_com_deficiencia: 'Transita com deficiência',
  recurso: 'Vai a recurso',
  aprovado_recurso: 'Aprovado no recurso',
  reprovado_recurso: 'Não aprovado no recurso',
  reprovado: 'Retido',
  reprovado_negativas: 'Retido por excesso de negativas',
  EEF: 'Reprovado por faltas',
  incompleto: 'Resultado por apurar',
  pendente: 'Recurso por concluir',
};

const SITUACOES_DISCIPLINA = {
  aprovado: 'Aprovado',
  transita_com_deficiencia: 'Transita com deficiência',
  recurso: 'Vai a recurso',
  reprovado: 'Reprovado',
  aprovado_recurso: 'Aprovado no recurso',
  reprovado_recurso: 'Não aprovado no recurso',
  pendente: 'Recurso por concluir',
};

function resultadoLabel(situacao) {
  return RESULTADOS[situacao] ?? situacao;
}

function disciplinaLabel(situacao) {
  return SITUACOES_DISCIPLINA[situacao] ?? situacao;
}

export function ResultadoFinal({ resultado }) {
  if (!resultado) {
    return null;
  }

  const temSituacaoEspecial = resultado.disciplinas?.some((disciplina) =>
    ['transita_com_deficiencia', 'recurso', 'reprovado'].includes(
      disciplina.situacao,
    ),
  );

  return (
    <Card>
      <CardHeader>
        <CardTitle>Resultado final</CardTitle>
        <CardDescription>
          Situação académica da classe selecionada
        </CardDescription>
        <Badge variant="outline" className="w-fit">
          {resultadoLabel(resultado.situacao)}
        </Badge>
        {resultado.situacao_academica === 'recurso' &&
          resultado.situacao_recurso &&
          resultado.situacao_recurso !== 'pendente' && (
            <p className="text-sm text-muted-foreground">
              Resultado do recurso; a classificação académica original continua
              registada como recurso.
            </p>
          )}
      </CardHeader>

      {resultado.situacao_academica === 'recurso' &&
        resultado.situacao_recurso === 'pendente' && (
          <CardContent>
            <p className="text-sm text-muted-foreground">
              Aguarde o lançamento e a publicação das notas de recurso.
            </p>
          </CardContent>
        )}

      {temSituacaoEspecial && (
        <CardContent className="flex flex-col gap-2">
          {resultado.disciplinas
            .filter((disciplina) =>
              [
                'transita_com_deficiencia',
                'recurso',
                'reprovado',
              ].includes(disciplina.situacao),
            )
            .map((disciplina) => {
              const situacao =
                disciplina.situacao_recurso ?? disciplina.situacao;

              return (
                <div
                  key={disciplina.disciplina_id}
                  className="flex flex-wrap items-center justify-between gap-2 border-t pt-2"
                >
                  <span className="text-sm font-medium">
                    {disciplina.disciplina}
                  </span>
                  <span className="text-sm text-muted-foreground">
                    {disciplinaLabel(situacao)}
                    {disciplina.media_recurso !== null &&
                      disciplina.media_recurso !== undefined &&
                      ` · ${disciplina.media_recurso}`}
                  </span>
                </div>
              );
            })}
        </CardContent>
      )}
    </Card>
  );
}
