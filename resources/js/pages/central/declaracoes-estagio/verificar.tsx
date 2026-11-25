import { Head } from '@inertiajs/react';
import { CircleAlert, Clock, ExternalLink } from 'lucide-react';
import { useState } from 'react';
import type { ReactNode } from 'react';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';

type DeclaracaoVerificada = {
  aluno_nome: string;
  instituicao_nome: string;
  nota: string | null;
  avaliacao: string | null;
  empresa_nome: string;
  emitido_em: string;
  duracao: string;
  carga_horaria: string;
  estagio_realizado: boolean;
} | null;

type Empresa = {
  nome: string;
  nif: string;
  morada: string;
  telefones: Array<{ texto: string; link: string }>;
  email: string;
  logo: string;
  iniciais: string;
};

function Secao({ titulo, children }: { titulo: string; children: ReactNode }) {
  return (
    <section className="grid gap-2">
      <h2 className="text-xs font-semibold tracking-wide text-muted-foreground uppercase">
        {titulo}
      </h2>
      {children}
    </section>
  );
}

function ListaDados({ children }: { children: ReactNode }) {
  return (
    <Card className="gap-0 py-0 shadow-none">
      <CardContent className="p-0">
        <dl className="divide-y text-sm">{children}</dl>
      </CardContent>
    </Card>
  );
}

function Linha({ rotulo, valor }: { rotulo: string; valor: string }) {
  return (
    <div className="grid gap-0.5 px-4 py-2 sm:grid-cols-[150px_1fr] sm:items-center sm:gap-4">
      <dt className="text-muted-foreground">{rotulo}</dt>
      <dd className="font-medium sm:text-right">{valor}</dd>
    </div>
  );
}

function Divisor({ titulo }: { titulo: string }) {
  return (
    <div className="bg-muted/40 px-4 py-1.5 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
      {titulo}
    </div>
  );
}

function CabecalhoEmpresa({ empresa }: { empresa: Empresa }) {
  const [logoDisponivel, setLogoDisponivel] = useState(true);

  return (
    <div className="flex flex-col items-center gap-1">
      <div className="grid size-16 shrink-0 place-items-center overflow-hidden border bg-background">
        {logoDisponivel ? (
          <img
            src={empresa.logo}
            alt={`Logótipo da ${empresa.nome}`}
            className="size-full object-contain p-2"
            onError={() => setLogoDisponivel(false)}
          />
        ) : (
          <span
            className="text-xl font-bold text-primary"
            aria-label={`Iniciais de ${empresa.nome}`}
          >
            {empresa.iniciais}
          </span>
        )}
      </div>
      <p className="text-xl font-bold tracking-tight">{empresa.nome}</p>
    </div>
  );
}

function ContactosEmpresa({ empresa }: { empresa: Empresa }) {
  return (
    <div className="grid gap-1 text-center text-sm text-muted-foreground">
      <p>
        <span className="font-bold text-primary">{empresa.nome}</span>
        <span aria-hidden="true">{'  |  '}</span>
        NIF: {empresa.nif}
      </p>
      <p>{empresa.morada}</p>
      <p>
        Tel.:{' '}
        {empresa.telefones.map((telefone, indice) => (
          <span key={telefone.link}>
            {indice > 0 && <span aria-hidden="true"> / </span>}
            <a
              className="underline-offset-4 hover:text-foreground hover:underline"
              href={`tel:${telefone.link}`}
            >
              {telefone.texto}
            </a>
          </span>
        ))}
        <span aria-hidden="true">{'  |  '}</span>
        E-mail:{' '}
        <a
          className="underline-offset-4 hover:text-foreground hover:underline"
          href={`mailto:${empresa.email}`}
        >
          {empresa.email}
        </a>
      </p>
    </div>
  );
}

export default function Verificar({
  declaracao,
  verificadoEm,
  empresa,
  empresaSiteUrl,
}: {
  declaracao: DeclaracaoVerificada;
  verificadoEm: string;
  empresa: Empresa;
  empresaSiteUrl: string;
}) {
  return (
    <>
      <Head title="Verificação de estágio" />
      <main className="grid min-h-svh w-full place-items-center px-4 py-4">
        <div className="w-full max-w-3xl">
          <Card className="gap-4 py-4">
            <CardHeader className="items-center gap-1 border-b pb-4 text-center">
              <CabecalhoEmpresa empresa={empresa} />
              {!declaracao && (
                <CircleAlert
                  className="size-10 text-destructive"
                  aria-hidden="true"
                />
              )}
              <CardTitle className="mt-1 text-lg">
                {declaracao ? 'Declaração válida' : 'Declaração não encontrada'}
              </CardTitle>
              <CardDescription>
                {declaracao
                  ? `O registo abaixo confirma a realização do estágio na ${empresa.nome}.`
                  : 'Não foi possível confirmar uma declaração de estágio com este código.'}
              </CardDescription>
              <p className="mt-1 inline-flex items-center gap-1.5 text-xs text-muted-foreground">
                <Clock className="size-3.5" aria-hidden="true" />
                Verificado em {verificadoEm}
              </p>
            </CardHeader>

            <CardContent className="grid gap-4">
              {declaracao && (
                <>
                  <ListaDados>
                    <Divisor titulo="Aluno" />
                    <Linha rotulo="Nome" valor={declaracao.aluno_nome} />
                    <Linha
                      rotulo="Instituição"
                      valor={declaracao.instituicao_nome}
                    />
                    <Divisor titulo="Estágio" />
                    <Linha rotulo="Empresa" valor={declaracao.empresa_nome} />
                    <Linha rotulo="Duração" valor={declaracao.duracao} />
                    <Linha
                      rotulo="Carga horária"
                      valor={declaracao.carga_horaria}
                    />
                    <Linha rotulo="Emitida em" valor={declaracao.emitido_em} />
                  </ListaDados>

                  {declaracao.nota !== null && (
                    <Secao titulo="Avaliação">
                      <Card className="gap-0 py-0 shadow-none">
                        <CardContent className="grid grid-cols-2 gap-4 px-4 py-3">
                          <div>
                            <CardDescription className="text-xs">
                              Nota final
                            </CardDescription>
                            <CardTitle className="text-2xl">
                              {Number(declaracao.nota).toLocaleString('pt-AO', {
                                maximumFractionDigits: 1,
                              })}{' '}
                              <span className="text-sm font-medium text-muted-foreground">
                                valores
                              </span>
                            </CardTitle>
                          </div>
                          {declaracao.avaliacao && (
                            <div>
                              <CardDescription className="text-xs">
                                Classificação
                              </CardDescription>
                              <CardTitle className="text-2xl">
                                {declaracao.avaliacao}
                              </CardTitle>
                            </div>
                          )}
                        </CardContent>
                      </Card>
                    </Secao>
                  )}
                </>
              )}

              <div className="grid gap-4 border-t pt-4">
                <div className="flex justify-center">
                  <Button asChild variant="outline" size="sm">
                    <a
                      href={empresaSiteUrl}
                      target="_blank"
                      rel="noopener noreferrer"
                    >
                      Visitar tunganetu.it.ao
                      <ExternalLink className="size-3.5" aria-hidden="true" />
                    </a>
                  </Button>
                </div>
                <ContactosEmpresa empresa={empresa} />
              </div>

              {declaracao && (
                <p className="text-center text-[11px] text-muted-foreground">
                  Esta página confirma a autenticidade da declaração emitida
                  pela Empresa {empresa.nome}. Os dados apresentados correspondem ao
                  registo existente no momento da consulta.
                </p>
              )}
            </CardContent>
          </Card>
        </div>
      </main>
    </>
  );
}
