import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

import { Badge } from '@/components/ui/badge';
import { BadgeCheck } from 'lucide-react';

export default function VerificarCard({ certificado }) {
  if (!certificado) {
    return (
      <div className="mx-auto w-full max-w-xl space-y-6">
        <div className="flex flex-col items-center justify-center gap-2">
          <h1 className="text-lg text-muted-foreground">Carregando...</h1>
        </div>
      </div>
    );
  }

  const getBadgeStyle = (resultado) => {
    if (resultado === 'APTO') {
      return 'bg-green-50 text-green-500 dark:bg-green-100';
    }
    return 'bg-red-50 text-red-500 dark:bg-red-100';
  };

  return (
    <div className="mx-auto max-w-2xl space-y-6">
      {/* CABEÇALHO INSTITUCIONAL */}
      <div className="space-y-1 text-center">
        {/* Insígnia */}
        <div className="mb-1 flex justify-center">
          <img
            src="/Emblem_of_Angola.svg.png"
            alt="Insígnia da República de Angola"
            className="h-[60px] w-[60px] object-contain"
          />
        </div>

        {/* República
        <p className="text-xs uppercase text-wide">
          República de Angola
        </p> */}

        {/* Ministério */}
        <p className="text-wide text-xs uppercase">Ministério da Educação</p>

        {/* Instituição */}
        <p className="text-sm font-medium">{certificado.instituicao}</p>

        {/* Curso */}
        <p className="text-xs text-muted-foreground">{certificado.curso}</p>

        {/* Linha separadora */}
        <div className="mx-auto my-2 h-[1px] w-24 bg-gray-300"></div>

        {/* Título */}
        <h1 className="text-base font-semibold">
          Verificação de Autenticidade de Certificadosss
        </h1>
      </div>

      {/* STATUS */}
      <div className="space-y-2 text-center">
        <BadgeCheck size={50} className="mx-auto text-green-600" />

        <h2 className="text-lg font-semibold text-green-600">
          Documento Autêntico
        </h2>

        <p className="mx-auto max-w-md text-sm text-muted-foreground">
          Este certificado foi validado com sucesso no sistema oficial de gestão
          escolar.
        </p>
      </div>

      {/* DADOS DO ESTUDANTE */}
      <Card>
        <CardHeader className="border-b">
          <CardTitle>Identificação do Estudante</CardTitle>
        </CardHeader>

        <CardContent className="flex items-center gap-4">
          <div className="flex h-20 w-20 items-center justify-center rounded-full border text-muted-foreground">
            IMG
          </div>

          <div>
            <p className="font-medium">{certificado.nome}</p>
            <p className="text-sm text-muted-foreground">
              Nº do BI: {certificado.bi}
            </p>
            <p className="text-sm text-muted-foreground">
              Matrícula: {certificado.matricula}
            </p>
          </div>
        </CardContent>
      </Card>

      {/* DADOS DO CERTIFICADO */}
      <Card>
        <CardHeader className="border-b">
          <CardTitle>Dados do Certificado</CardTitle>
        </CardHeader>

        <CardContent className="grid grid-cols-2 gap-3 text-sm">
          <div>
            <span className="text-muted-foreground">
              Ano de conclusão do curso
            </span>
            <p>{certificado.ano_defesa}</p>
          </div>

          <div>
            <h3 className="text-muted-foreground">Resultado</h3>
            <span className="text-sm">
              <Badge className={getBadgeStyle(certificado.resultado_final)}>
                {certificado.resultado_final}
              </Badge>
            </span>
          </div>

          <div>
            <span className="text-muted-foreground">Média Final</span>
            <p>{Math.round(certificado.classificacao_final)} valores</p>
          </div>
        </CardContent>
      </Card>

      {/* RODAPÉ */}
      <div className="text-center text-xs text-muted-foreground">
        Sistema de Gestão Escolar • Documento verificado digitalmente
      </div>
    </div>
  );
}
