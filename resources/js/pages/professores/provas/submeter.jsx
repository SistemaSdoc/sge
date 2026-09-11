import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, AlertCircle, Loader2, Users, Lock } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Alert, AlertDescription } from '@/components/ui/alert';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

export default function Submeter({ prazo, turmas = [], turma_selecionada = null }) {
  //   Se veio turma do card, pré-seleciona. Senão, fica vazio.
  const { data, setData, post, processing, errors } = useForm({
    turma_id: turma_selecionada || '',
    arquivo_prova: null,
    arquivo_chave: null,
    comentario: '',
  });

  //   Verifica se a turma veio pré-selecionada
  const turmaVemDoCard = !!turma_selecionada;

  // Encontra o nome da turma pré-selecionada
  const turmaInfo = turmas.find((t) => t.id === turma_selecionada);

  const submit = (e) => {
    e.preventDefault();
    post(`/dashboard/professor/provas/submeter/${prazo.id}`, {
      forceFormData: true,
      onSuccess: () => {
        window.location.href = '/dashboard/professor/provas';
      },
    });
  };

  const hasErrors = Object.keys(errors).length > 0;

  return (
    <>
      <Head title={`Submeter: ${prazo.titulo}`} />

      <div className="w-full space-y-6 p-6 max-w-3xl mx-auto">
        {/* Cabeçalho */}
        <div className="flex flex-wrap items-center justify-between gap-4">
          <h1 className="text-2xl font-bold text-foreground">Submeter Prova</h1>
          <Button variant="outline" asChild>
            <Link href="/dashboard/professor/provas">
              <ArrowLeft className="mr-1.5 size-4" />
              Voltar
            </Link>
          </Button>
        </div>

        {/* Card do prazo */}
        <Card className="shadow-sm border-border">
          <CardHeader>
            <CardTitle className="text-lg font-semibold">{prazo.titulo}</CardTitle>
            <CardDescription>
              Disciplina: {prazo.disciplina?.nome || 'Todas'} | Limite: {prazo.data_limite}
            </CardDescription>
          </CardHeader>
        </Card>

        {/* Formulário */}
        <Card className="shadow-sm border-border">
          <CardContent className="pt-6">
            <form onSubmit={submit} encType="multipart/form-data" className="space-y-6">
              {hasErrors && (
                <Alert variant="destructive">
                  <AlertCircle className="size-4" />
                  <AlertDescription>
                    <ul className="list-disc pl-4 space-y-1 text-sm">
                      {Object.values(errors).map((msg, i) => (
                        <li key={i}>{msg}</li>
                      ))}
                    </ul>
                  </AlertDescription>
                </Alert>
              )}

              {/*   TURMA */}
              <div className="space-y-2">
                <Label className="font-medium flex items-center gap-2">
                  <Users className="size-4" />
                  Turma <span className="text-destructive">*</span>
                </Label>

                {turmaVemDoCard ? (
                  /*   Turma pré-selecionada (vinda do card) */
                  <div className="flex items-center gap-2 p-3 bg-muted/40 border border-border">
                    <Lock className="size-4 text-muted-foreground" />
                    <span className="font-medium">{turmaInfo?.nome || 'Turma selecionada'}</span>
                    <Badge variant="outline" className="ml-auto text-xs">
                      Fixa
                    </Badge>
                  </div>
                ) : (
                  /* Fallback: select quando não vem do card */
                  <Select
                    value={data.turma_id}
                    onValueChange={(value) => setData('turma_id', value)}
                  >
                    <SelectTrigger className="w-full">
                      <SelectValue placeholder="Selecione a turma..." />
                    </SelectTrigger>
                    <SelectContent>
                      {turmas.length === 0 ? (
                        <SelectItem value="-" disabled>
                          Nenhuma turma disponível
                        </SelectItem>
                      ) : (
                        turmas.map((t) => (
                          <SelectItem key={t.id} value={t.id}>
                            {t.nome}
                          </SelectItem>
                        ))
                      )}
                    </SelectContent>
                  </Select>
                )}

                <p className="text-xs text-muted-foreground">
                  {turmaVemDoCard
                    ? 'A turma foi selecionada a partir do card do prazo.'
                    : 'Selecione a turma para a qual está a submeter.'}
                </p>

                {errors.turma_id && (
                  <p className="text-sm text-destructive">{errors.turma_id}</p>
                )}
              </div>

              {/* Arquivo da Prova */}
              <div className="space-y-2">
                <Label htmlFor="arquivo_prova" className="font-medium">
                  Arquivo da Prova (PDF ou Word) <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="arquivo_prova"
                  type="file"
                  accept=".pdf,.doc,.docx"
                  onChange={(e) => setData('arquivo_prova', e.target.files[0])}
                  required
                  className="cursor-pointer"
                />
                <p className="text-sm text-muted-foreground">Máx. 10MB</p>
                {errors.arquivo_prova && (
                  <p className="text-sm text-destructive">{errors.arquivo_prova}</p>
                )}
              </div>

              {/* Chave / Gabarito */}
              <div className="space-y-2">
                <Label htmlFor="arquivo_chave" className="font-medium">
                  Chave / Gabarito (PDF ou Word) <span className="text-destructive">*</span>
                </Label>
                <Input
                  id="arquivo_chave"
                  type="file"
                  accept=".pdf,.doc,.docx"
                  onChange={(e) => setData('arquivo_chave', e.target.files[0])}
                  required
                  className="cursor-pointer"
                />
                <p className="text-sm text-muted-foreground">Máx. 10MB</p>
                {errors.arquivo_chave && (
                  <p className="text-sm text-destructive">{errors.arquivo_chave}</p>
                )}
              </div>

              {/* Comentário */}
              <div className="space-y-2">
                <Label htmlFor="comentario" className="font-medium">
                  Comentário (opcional)
                </Label>
                <Textarea
                  id="comentario"
                  rows={3}
                  value={data.comentario}
                  onChange={(e) => setData('comentario', e.target.value)}
                  placeholder="Observações sobre a prova..."
                  className="resize-none"
                />
              </div>

              {/* Botão de envio */}
              <div className="flex justify-end">
                <Button
                  type="submit"
                  disabled={processing || !data.turma_id}
                  className="min-w-[140px]"
                >
                  {processing ? (
                    <>
                      <Loader2 className="mr-1.5 size-4 animate-spin" />
                      Enviando...
                    </>
                  ) : (
                    'Submeter Prova'
                  )}
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </>
  );
}