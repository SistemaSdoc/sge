import { Head, router, Link } from '@inertiajs/react';
import { useState } from 'react';
import {
  ArrowLeft,
  Send,
  Loader2,
  Calendar,
  BookOpen,
  Users,
  Clock,
  AlertCircle,
  CheckCircle,
  XCircle,
} from 'lucide-react';
import { toast } from 'sonner';
import { Button } from '@/components/ui/button';
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';

export default function Create({ prazo, justificativa }) {
  const [motivo, setMotivo] = useState(justificativa?.motivo || '');
  const [loading, setLoading] = useState(false);

  const isBlocked = justificativa?.status === 'aceita' || justificativa?.status === 'recusada';
  const prazoEncerrado = prazo.status !== 'aberto';

  const getStatusBadge = (status) => {
    const configs = {
      pendente: { label: 'Pendente', className: 'border-yellow-500 text-yellow-700 bg-yellow-50 dark:bg-yellow-950/20' },
      aceita: { label: 'Aceite', className: 'border-green-500 text-green-700 bg-green-50 dark:bg-green-950/20' },
      recusada: { label: 'Recusada', className: 'border-red-500 text-red-700 bg-red-50 dark:bg-red-950/20' },
    };
    const config = configs[status] || configs.pendente;
    return <Badge variant="outline" className={config.className}>{config.label}</Badge>;
  };

  const getStatusIcon = (status) => {
    if (status === 'pendente') return <AlertCircle className="size-4 mr-1" />;
    if (status === 'aceita') return <CheckCircle className="size-4 mr-1" />;
    if (status === 'recusada') return <XCircle className="size-4 mr-1" />;
    return null;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!motivo.trim()) {
      toast.error('Por favor, informe o motivo da não submissão.');
      return;
    }
    if (motivo.trim().length < 10) {
      toast.warning('O motivo deve ter pelo menos 10 caracteres.');
      return;
    }

    setLoading(true);
    router.post(
      `/dashboard/professor/prazos/${prazo.id}/justificar`,
      { motivo: motivo.trim() },
      {
        onSuccess: () => {
          toast.success('Justificativa enviada com sucesso! Aguarde a avaliação do diretor.');
          router.visit('/dashboard/professor/provas');
        },
        onError: (errors) => {
          const msg = errors?.motivo || 'Erro ao enviar justificativa. Tente novamente.';
          toast.error(msg);
        },
        onFinish: () => setLoading(false),
      }
    );
  };

  return (
    <>
      <Head title="Justificar não submissão" />
      <div className="max-w-3xl mx-auto p-4 sm:p-6">
        {/* Botão voltar */}
        <Button variant="ghost" size="sm" asChild className="mb-4">
          <Link href="/dashboard/professor/provas">
            <ArrowLeft className="size-4 mr-1" />
            Voltar para provas
          </Link>
        </Button>

        <Card className="shadow-lg border-border/50">
          {/* Cabeçalho com cor de fundo */}
          <div className="bg-primary/5 dark:bg-primary/10 rounded-t-lg border-b">
            <CardHeader>
              <div className="flex items-start justify-between">
                <div>
                  <CardTitle className="text-2xl flex items-center gap-2">
                    <AlertCircle className="size-6 text-primary" />
                    Justificar não submissão
                  </CardTitle>
                  <CardDescription className="mt-2">
                    Preencha o formulário abaixo para justificar a não submissão da prova.
                  </CardDescription>
                </div>
                {justificativa && (
                  <div className="flex items-center gap-1">
                    {getStatusIcon(justificativa.status)}
                    {getStatusBadge(justificativa.status)}
                  </div>
                )}
              </div>
            </CardHeader>
          </div>

          {/* Informações do prazo */}
          <div className="bg-muted/30 px-6 py-4 border-b border-border/50">
            <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-sm">
              <div className="flex items-center gap-2">
                <BookOpen className="size-4 text-muted-foreground" />
                <span className="font-medium">Disciplina:</span>
                <span>{prazo.disciplina?.nome || 'Todas'}</span>
              </div>
              <div className="flex items-center gap-2">
                <Users className="size-4 text-muted-foreground" />
                <span className="font-medium">Turma:</span>
                <span>{prazo.classe?.nome || 'Todas'}</span>
              </div>
              <div className="flex items-center gap-2">
                <Calendar className="size-4 text-muted-foreground" />
                <span className="font-medium">Data limite:</span>
                <span>{prazo.data_limite}</span>
              </div>
              <div className="flex items-center gap-2">
                <Clock className="size-4 text-muted-foreground" />
                <span className="font-medium">Status do prazo:</span>
                <Badge variant={prazoEncerrado ? 'destructive' : 'default'} className="text-xs">
                  {prazoEncerrado ? 'Encerrado' : 'Aberto'}
                </Badge>
              </div>
            </div>
          </div>

          <form onSubmit={handleSubmit}>
            <CardContent className="space-y-6 pt-6">
              {/* Aviso se o prazo estiver encerrado */}
              {prazoEncerrado && (
                <div className="p-3 bg-amber-50 dark:bg-amber-950/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-start gap-2">
                  <AlertCircle className="size-5 text-amber-600 dark:text-amber-400 mt-0.5 flex-shrink-0" />
                  <div>
                    <p className="text-sm font-medium text-amber-800 dark:text-amber-300">
                      Prazo encerrado
                    </p>
                    <p className="text-xs text-amber-700 dark:text-amber-400">
                      Este prazo já foi encerrado. A justificativa ainda pode ser enviada para análise do diretor.
                    </p>
                  </div>
                </div>
              )}

              {/* Campo do motivo */}
              <div className="space-y-2">
                <label htmlFor="motivo" className="text-sm font-medium flex items-center gap-1">
                  Motivo da não submissão <span className="text-destructive">*</span>
                </label>
                <Textarea
                  id="motivo"
                  value={motivo}
                  onChange={(e) => setMotivo(e.target.value)}
                  placeholder="Descreva detalhadamente o motivo pelo qual não pôde submeter a prova (mínimo 10 caracteres)..."
                  rows={6}
                  disabled={loading || isBlocked}
                  className="resize-none focus:ring-2 focus:ring-primary/20 transition-all"
                />
                <p className="text-xs text-muted-foreground">
                  {motivo.trim().length === 0
                    ? 'Campo obrigatório'
                    : `${motivo.trim().length} caracteres (mínimo 10)`}
                </p>
              </div>

              {/* Justificativa existente com status */}
              {justificativa && (
                <>
                  <Separator />
                  <div className="p-4 bg-muted/40 rounded-lg border border-border/50">
                    <p className="text-sm font-medium flex items-center gap-2">
                      Status da justificativa
                      {getStatusBadge(justificativa.status)}
                    </p>
                    {isBlocked && (
                      <p className="text-xs text-muted-foreground mt-2 flex items-center gap-1">
                        <AlertCircle className="size-3" />
                        Esta justificativa já foi avaliada e não pode mais ser alterada.
                      </p>
                    )}
                    {justificativa.motivo && (
                      <div className="mt-2 p-2 bg-background rounded border border-border/30">
                        <p className="text-xs text-muted-foreground font-medium">Motivo enviado:</p>
                        <p className="text-sm mt-0.5">{justificativa.motivo}</p>
                      </div>
                    )}
                  </div>
                </>
              )}
            </CardContent>

            <CardFooter className="flex flex-col-reverse sm:flex-row sm:justify-end gap-3 pt-2 pb-6 border-t border-border/50 mt-2">
              <Button
                type="button"
                variant="outline"
                onClick={() => router.visit('/dashboard/professor/provas')}
                disabled={loading}
                className="w-full sm:w-auto"
              >
                Cancelar
              </Button>
              <Button
                type="submit"
                disabled={loading || isBlocked}
                className="w-full sm:w-auto min-w-[160px]"
              >
                {loading ? (
                  <>
                    <Loader2 className="size-4 mr-2 animate-spin" />
                    Enviando...
                  </>
                ) : (
                  <>
                    <Send className="size-4 mr-2" />
                    Enviar justificativa
                  </>
                )}
              </Button>
            </CardFooter>
          </form>
        </Card>
      </div>
    </>
  );
}