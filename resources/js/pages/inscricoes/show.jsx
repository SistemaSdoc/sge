import { usePage } from '@inertiajs/react';
import { Card, CardContent } from '@/components/ui/card';
import { Minus } from 'lucide-react';
import { formatStatusInscricao } from '@/utils/format-status';

export default function Show() {
  const { inscricao } = usePage().props;

  return (
    <div className="mx-auto w-full max-w-6xl space-y-6 p-6">
      <Card className="overflow-hidden pt-0!">
        <div className="relative flex items-end w-full h-56 bg-muted">
          <div className="absolute inset-0 bg-black/50" />

          <div className="relative z-10 p-6 space-y-2 text-white">
            <h1 className="text-2xl font-semibold wrap-break-word md:text-3xl">
              {inscricao.candidato.nome}
            </h1>
            <p className="text-sm break-all opacity-90">
              {inscricao.candidato.telefone} — {inscricao.candidato.email}
            </p>
          </div>
        </div>

        <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
          <div>
            <p className="text-sm text-muted-foreground">Morada</p>
            <p className="font-medium">
              {inscricao.candidato.morada || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Curso</p>
            <p className="font-medium">
              {inscricao.curso || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Turno</p>
            <p className="font-medium">
              {inscricao.turno || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Instituição</p>
            <p className="font-medium">
              {inscricao.instituicao || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nº Bilhete</p>
            <p className="font-medium">
              {inscricao.candidato.bi || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nº Estudante</p>
            <p className="font-medium">
              {inscricao.candidato.numero_estudante || <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Nota da prova</p>
            <p className="font-medium">
              {inscricao.candidato.nota_teste ?? <Minus size={15} className="text-muted-foreground" />}
            </p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Status</p>
            <p className="font-medium">{formatStatusInscricao(inscricao.status)}</p>
          </div>

          <div>
            <p className="text-sm text-muted-foreground">Inscrição realizada em</p>
            <p className="font-medium">{inscricao.created_at}</p>
          </div>
        </CardContent>
      </Card>
    </div>
  );
}