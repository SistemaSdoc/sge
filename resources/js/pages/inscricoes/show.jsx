import { usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Minus } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Download } from 'lucide-react';
import { formatStatusInscricao } from '@/utils/format-status';

export default function Show() {
  const {
    inscricao,
    entity_label: entityLabelProp,
    tem_nota_teste: temNotaTeste,
  } = usePage().props;
  const candidato = inscricao?.candidato ?? {};
  const entityLabel = entityLabelProp || 'Matrícula';

  const renderValue = (value) => {
    if (value === null || value === undefined || value === '') {
      return <Minus size={15} className="text-muted-foreground" />;
    }

    return value;
  };

  const splitFiliacao = (value) => {
    if (typeof value !== 'string') {
      return [];
    }

    return value
      .split(/\s+e\s+/i)
      .map((part) => part.trim())
      .filter(Boolean);
  };

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex h-56 w-full items-end bg-muted">
          <div className="absolute inset-0 bg-black/50" />

          <div className="relative z-10 space-y-2 p-6 text-white">
            <h1 className="text-2xl font-semibold wrap-break-word md:text-3xl">
              {candidato.nome || 'Sem nome'}
            </h1>
            <p className="text-sm break-all opacity-90">
              {renderValue(candidato.telefone)} — {renderValue(candidato.email)}
            </p>
          </div>
        </div>
        
        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
          <div>
            <p className="text-sm text-muted-foreground">Ano Lectivo</p>
            <p className="font-medium">{renderValue(inscricao.ano_lectivo)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Curso</p>
            <p className="font-medium">{renderValue(inscricao.curso)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Turno</p>
            <p className="font-medium">{renderValue(inscricao.turno)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">{entityLabel}</p>
            <p className="font-medium">{renderValue(inscricao.instituicao)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nome do estudante</p>
            <p className="font-medium">{renderValue(candidato.nome)}</p>
          </div>

          {splitFiliacao(candidato.filiacao)[0] && (
            <div>
              <p className="text-sm text-muted-foreground">Nome do pai</p>
              <p className="font-medium">
                {renderValue(splitFiliacao(candidato.filiacao)[0])}
              </p>
            </div>
          )}

          {splitFiliacao(candidato.filiacao)[1] && (
            <div>
              <p className="text-sm text-muted-foreground">Nome da mãe</p>
              <p className="font-medium">
                {renderValue(splitFiliacao(candidato.filiacao)[1])}
              </p>
            </div>
          )}

          <div>
            <p className="text-sm text-muted-foreground">Data de nascimento</p>
            <p className="font-medium">
              {renderValue(candidato.data_nascimento)}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nacionalidade</p>
            <p className="font-medium">
              {renderValue(candidato.nacionalidade)}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Naturalidade</p>
            <p className="font-medium">{renderValue(candidato.naturalidade)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Morada</p>
            <p className="font-medium">{renderValue(candidato.morada)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nº Bilhete</p>
            <p className="font-medium">{renderValue(candidato.bi)}</p>
          </div>
          {/*  nº de estudante 
          <div>
            <p className="text-sm text-muted-foreground">Nº Estudante</p>
            <p className="font-medium">
              {renderValue(candidato.numero_estudante)}
            </p>
          </div>
*/}
          {temNotaTeste && (
            <div>
              <p className="text-sm text-muted-foreground">Nota da prova</p>
              <p className="font-medium">
                {inscricao.nota_teste ?? (
                  <Minus size={15} className="text-muted-foreground" />
                )}
              </p>
            </div>
          )}

          <div>
            <p className="text-sm text-muted-foreground">Status</p>
            <p className="font-medium">
              {formatStatusInscricao(inscricao.status)}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">
              {entityLabel} realizada em
            </p>
            <p className="font-medium">{inscricao.created_at}</p>
          </div>

          {inscricao?.aluno_id && (
            <div className="md:col-span-3 mt-6 pt-4 border-t flex justify-end">
              <Button
                type="button"
                variant="default"
                size="sm"
                className="inline-flex items-center gap-2 rounded-md bg-primary px-4 py-2 text-sm font-medium text-primary-foreground shadow-sm transition hover:opacity-90"
                onClick={() =>
                  window.open(
                    `/dashboard/alunos/${inscricao.aluno_id}/ficha-matricula`,
                    '_blank',
                  )
                }
              >
                <Download className="size-4" />
                Ficha de Matrícula (PDF)
              </Button>
            </div>
          )}
        </CardContent>
      </Card>
    </div>
  );
}