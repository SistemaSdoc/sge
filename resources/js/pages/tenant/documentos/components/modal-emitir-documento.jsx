import { AlertCircle, Download, Loader2, X } from 'lucide-react';
import { useState } from 'react';

import { Button } from '@/components/ui/button';
import {
  Command,
  CommandEmpty,
  CommandGroup,
  CommandInput,
  CommandItem,
  CommandList,
} from '@/components/ui/command';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Field,
  FieldDescription,
  FieldError,
  FieldGroup,
  FieldLabel,
} from '@/components/ui/field';
import { Input } from '@/components/ui/input';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { usePesquisaAlunos } from '../hooks/use-pesquisa-alunos';

export function ModalEmitirDocumento({ documento, classes, open, onClose }) {
  const [query, setQuery] = useState('');
  const [alunoSeleccionado, setAlunoSeleccionado] = useState(null);
  const [classeId, setClasseId] = useState('');
  const [efeito, setEfeito] = useState('');
  const [erro, setErro] = useState(null);
  const [loading, setLoading] = useState(false);

  const { resultados, searching, notFound, queryActual, pesquisar, limpar } =
    usePesquisaAlunos();

  const nomeDocumento = (documento?.nome ?? '').trim().toLowerCase();
  const requerSelectClasse =
    documento?.subtipo === 'declaracao_sem_notas' ||
    documento?.subtipo === 'declaracao_com_notas';

  function handleClose() {
    setQuery('');
    setAlunoSeleccionado(null);
    setClasseId('');
    setErro(null);
    limpar();
    onClose();
  }

  function handleSeleccionarAluno(aluno) {
    setAlunoSeleccionado(aluno);
    setErro(null);
    setClasseId('');
    setQuery('');
    limpar();
  }

  function handleCommandInput(value) {
    setQuery(value);
    if (value.trim().length >= 3) {
      pesquisar(value);
    } else if (!value) {
      limpar();
    }
  }

  async function handleSubmit() {
    setErro(null);

    if (!alunoSeleccionado) {
      setErro('Pesquise e seleccione o aluno antes de exportar.');
      return;
    }

    if (requerSelectClasse && !classeId) {
      setErro('Seleccione a classe para este documento.');
      return;
    }

    const params = new URLSearchParams({
      item_pagavel_id: documento.id,
      aluno_id: alunoSeleccionado.id,
      ...(requerSelectClasse && classeId ? { classe_id: classeId } : {}),
      ...(efeito ? { efeito } : {}),
    });

    setLoading(true);

    try {
      const res = await fetch(
        `/dashboard/documentos/exportar?${params.toString()}`,
        {
          method: 'GET',
          credentials: 'same-origin',
          headers: { Accept: '*/*' },
        },
      );

      const contentType = res.headers.get('content-type') || '';

      if (res.ok && contentType.includes('application/pdf')) {
        const blob = await res.blob();
        const url = URL.createObjectURL(blob);
        window.open(url, '_blank');
        handleClose();
        return;
      }

      try {
        const data = await res.json();
        setErro(data.message || 'Ocorreu um erro ao gerar o documento.');
      } catch {
        setErro('Ocorreu um erro ao gerar o documento.');
      }
    } catch {
      setErro('Ocorreu um erro ao contactar o servidor.');
    } finally {
      setLoading(false);
    }
  }

  if (!documento) return null;

  return (
    <Dialog open={open} onOpenChange={handleClose}>
      <DialogContent className="gap-0 p-0 sm:max-w-md sm:rounded-none">
        {/* Cabeçalho */}
        <DialogHeader className="border-b px-6 py-4">
          <DialogTitle className="">{documento.nome}</DialogTitle>
        </DialogHeader>

        <div className="space-y-6 px-6 py-5">
          {/* Pesquisa */}
          <FieldGroup>
            <Field>
              <FieldLabel>
                Aluno <span className="text-red-500">*</span>
              </FieldLabel>

              {/* Aluno seleccionado — mostra em vez do Command */}
              {alunoSeleccionado ? (
                <div className="flex items-center justify-between border border-slate-200 bg-slate-50 px-3 py-2.5">
                  <div className="min-w-0">
                    <p className="truncate text-sm font-medium text-slate-800">
                      {alunoSeleccionado.nome}
                    </p>
                    <p className="truncate text-xs text-muted-foreground">
                      {alunoSeleccionado.curso} · {alunoSeleccionado.classe} ·{' '}
                      {alunoSeleccionado.matricula}
                    </p>
                  </div>
                  <button
                    type="button"
                    onClick={() => setAlunoSeleccionado(null)}
                    className="ml-3 shrink-0 text-muted-foreground hover:text-foreground"
                  >
                    <X className="h-4 w-4" />
                  </button>
                </div>
              ) : (
                <Command className="rounded-none border border-slate-200">
                  <CommandInput
                    placeholder="Nº de processo ou nome…"
                    value={query}
                    onValueChange={handleCommandInput}
                  />
                  {query.trim().length >= 3 && (
                    <CommandList className="max-h-48 overflow-y-auto">
                      {searching && (
                        <div className="flex items-center justify-center gap-2 py-4 text-sm text-muted-foreground">
                          <Loader2 className="h-4 w-4 animate-spin" />A
                          pesquisar…
                        </div>
                      )}
                      {!searching && notFound && (
                        <CommandEmpty>
                          Nenhum resultado para "{queryActual}".
                        </CommandEmpty>
                      )}
                      {!searching && resultados.length > 0 && (
                        <CommandGroup
                          heading={
                            resultados.length === 1
                              ? '1 aluno encontrado'
                              : `${resultados.length} alunos encontrados`
                          }
                        >
                          {resultados.map((aluno) => (
                            <CommandItem
                              key={aluno.id}
                              value={`${aluno.nome} ${aluno.matricula}`}
                              onSelect={() => handleSeleccionarAluno(aluno)}
                            >
                              <div className="min-w-0 flex-1">
                                <p className="truncate text-sm font-medium">
                                  {aluno.nome}
                                </p>
                                <p className="truncate text-xs text-muted-foreground">
                                  {aluno.curso} · {aluno.classe} ·{' '}
                                  {aluno.matricula}
                                </p>
                              </div>
                            </CommandItem>
                          ))}
                        </CommandGroup>
                      )}
                    </CommandList>
                  )}
                </Command>
              )}
            </Field>
          </FieldGroup>

          {/* Classe + Efeito */}
          {requerSelectClasse && alunoSeleccionado && (
            <FieldGroup>
              <div className="grid grid-cols-2 gap-4">
                <Field>
                  <FieldLabel htmlFor="classe">
                    Classe <span className="text-red-500">*</span>
                  </FieldLabel>
                  <Select value={classeId} onValueChange={setClasseId}>
                    <SelectTrigger id="classe">
                      <SelectValue placeholder="Seleccione…" />
                    </SelectTrigger>
                    <SelectContent>
                      {(alunoSeleccionado.classes ?? classes ?? []).map((c) => (
                        <SelectItem
                          key={c.curso_classe_id ?? c.id}
                          value={String(c.curso_classe_id ?? c.id)}
                        >
                          {(c.curso ? `${c.curso} · ` : '') +
                            (c.classe ?? c.nome)}
                        </SelectItem>
                      ))}
                    </SelectContent>
                  </Select>
                </Field>

                <Field>
                  <FieldLabel htmlFor="efeito">Efeito</FieldLabel>
                  <Input
                    id="efeito"
                    placeholder="Ex: de frequência…"
                    value={efeito}
                    onChange={(e) => setEfeito(e.target.value)}
                  />
                </Field>
              </div>
            </FieldGroup>
          )}

          {/* Erro */}
          {erro && (
            <div className="flex items-start gap-2.5 border border-red-100 bg-red-50 px-4 py-3">
              <AlertCircle className="mt-0.5 h-4 w-4 shrink-0 text-red-500" />
              <p className="text-sm text-red-700">{erro}</p>
            </div>
          )}
        </div>

        {/* Rodapé */}
        <div className="flex justify-end gap-3 px-6 py-4">
          <Button variant="outline" onClick={handleClose} disabled={loading}>
            Cancelar
          </Button>
          <Button
            onClick={handleSubmit}
            disabled={loading || !alunoSeleccionado}
          >
            {loading ? (
              <Loader2 className="mr-2 size-4 animate-spin" />
            ) : (
              <Download className="mr-2 size-4" />
            )}
            Exportar PDF
          </Button>
        </div>
      </DialogContent>
    </Dialog>
  );
}
