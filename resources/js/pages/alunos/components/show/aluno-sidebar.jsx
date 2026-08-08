import {
  Card,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
//import { Progress } from '@/components/ui/progress';
import { Minus } from 'lucide-react';

function splitFiliacao(value) {
  if (typeof value !== 'string') return [];
  return value
    .split(/\s+e\s+/i)
    .map((p) => p.trim())
    .filter(Boolean);
}

export function AlunoSidebar({ aluno }) {
  const camposChave = [
    aluno.bi,
    aluno.telefone,
    aluno.email,
    aluno.nacionalidade,
    aluno.naturalidade,
    aluno.morada,
    aluno.filiacao,
    aluno.data_nascimento,
    aluno.foto_url,
  ];

  const preenchidos = camposChave.filter(Boolean).length;
  const percentagem = Math.round((preenchidos / camposChave.length) * 100);

  const [pai, mae] = splitFiliacao(aluno?.filiacao);

  return (
    <>
      {/*

      TODO: Implementar mais tarde verificação de dados completos do perfil do aluno

      <Card>
        <CardHeader>
          <CardTitle>Completar perfil</CardTitle>
          <CardDescription>
            Preecha os dados restantes completar o perfil
          </CardDescription>
        </CardHeader>

        <CardContent>
          <div className="flex items-center gap-3">
            <Progress value={percentagem} className="h-2" />
            <span className="text-sm font-medium tabular-nums">
              {percentagem}%
            </span>
          </div>
        </CardContent>
      </Card>*/}

      <Card>
        <CardHeader>
          <CardTitle>Sobre</CardTitle>
        </CardHeader>

        <CardContent className="space-y-4">
          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Nº Bilhete</p>
            <p className="truncate text-sm font-medium">
              {aluno?.bi || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Data de nascimento</p>
            <p className="truncate text-sm font-medium">
              {aluno?.data_nascimento || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Nacionalidade</p>
            <p className="truncate text-sm font-medium">
              {aluno?.nacionalidade || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Morada</p>
            <p className="truncate text-sm font-medium">
              {aluno?.morada || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Filiação</CardTitle>
        </CardHeader>

        <CardContent className="space-y-4">
          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Filho de</p>
            <p className="truncate text-sm font-medium">
              {pai || <Minus size={14} className="text-muted-foreground" />}
            </p>
          </div>

          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">e de</p>
            <p className="truncate text-sm font-medium">
              {mae || <Minus size={14} className="text-muted-foreground" />}
            </p>
          </div>
        </CardContent>
      </Card>

      <Card>
        <CardHeader>
          <CardTitle>Contacto</CardTitle>
        </CardHeader>

        <CardContent className="space-y-4">
          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Telefone</p>
            <p className="truncate text-sm font-medium">
              {aluno?.telefone || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>

          <div className="min-w-0">
            <p className="text-xs text-muted-foreground">Email</p>
            <p className="truncate text-sm font-medium">
              {aluno?.email || (
                <Minus size={14} className="text-muted-foreground" />
              )}
            </p>
          </div>
        </CardContent>
      </Card>
    </>
  );
}
