import { Card, CardContent } from '@/components/ui/card';
import { Minus } from 'lucide-react';

export function AlunoDetails({ aluno }) {
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

  const filiacaoParts = splitFiliacao(aluno?.filiacao);
  const pai = filiacaoParts[0];
  const mae = filiacaoParts[1];

  return (
    <Card>
      <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-4">
        <div>
          <p className="text-sm text-muted-foreground">Nº Bilhete</p>
          <p className="font-medium">
            {aluno?.bi || <Minus size={15} className="text-muted-foreground" />}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Telefone</p>
          <p className="font-medium">
            {aluno?.telefone || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Email</p>
          <p className="font-medium">
            {aluno?.email || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Matrícula</p>
          <p className="font-medium">
            {aluno?.matricula || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Nacionalidade</p>
          <p className="font-medium">
            {aluno?.nacionalidade || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Naturalidade</p>
          <p className="font-medium">
            {aluno?.naturalidade || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Morada</p>
          <p className="font-medium">
            {aluno?.morada || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Nome do pai</p>
          <p className="font-medium">{renderValue(pai)}</p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Nome da mãe</p>
          <p className="font-medium">{renderValue(mae)}</p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Data de nascimento</p>
          <p className="font-medium">
            {aluno?.data_nascimento || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
        <div>
          <p className="text-sm text-muted-foreground">Nº Processo</p>
          <p className="font-medium">
            {aluno.numero_processo || (
              <Minus size={15} className="text-muted-foreground" />
            )}
          </p>
        </div>
      </CardContent>
    </Card>
  );
}
