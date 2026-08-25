import { AlertCircle, Download, Loader2, Search, X } from 'lucide-react';
import { useRef, useState } from 'react';

import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import {
  Field,
  FieldDescription,
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

function AlunoItem({ aluno, seleccionado, onSeleccionar }) {
  const activo = seleccionado?.id === aluno.id;

  return (
    <button
      type="button"
      onClick={() => onSeleccionar(activo ? null : aluno)}
      className={[
        'flex w-full items-center gap-3 rounded-lg border px-4 py-3 text-left transition-colors',
        activo
          ? 'border-blue-300 bg-blue-50 ring-1 ring-blue-200'
          : 'border-slate-200 bg-white hover:border-blue-200 hover:bg-blue-50/40',
      ].join(' ')}
    >
      <div className="min-w-0 flex-1">
        <p className="truncate font-medium text-slate-800">{aluno.nome}</p>
        <p className="truncate text-xs text-slate-500">
          {aluno.curso} · {aluno.classe} · {aluno.matricula}
        </p>
      </div>
    </button>
  );
}

export function ModalEmitirDocumento({ documento, classes, open, onClose }) {
  const [query, setQuery] = useState('');
  const [alunoSeleccionado, setAlunoSeleccionado] = useState(null);
  const [classeId, setClasseId] = useState('');
  const [efeito, setEfeito] = useState('');
  const [erro, setErro] = useState(null);
  const [loading, setLoading] = useState(false);

  const inputRef = useRef(null);
  const { resultados, searching, notFound, queryActual, pesquisar, limpar } =
    usePesquisaAlunos();

  const nomeDocumento = (documento?.nome ?? '').trim().toLowerCase();
  const requerSelectClasse =
    nomeDocumento.includes('declaração com notas') ||
    nomeDocumento.includes('declaracao com notas') ||
    nomeDocumento.includes('declaração sem notas') ||
    nomeDocumento.includes('declaracao sem notas');

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
  }

  function handleLimparPesquisa() {
    setQuery('');
    setAlunoSeleccionado(null);
    limpar();
    inputRef.current?.focus();
  }

  function handleKeyDown(e) {
    if (e.key === 'Enter') {
      e.preventDefault();
      pesquisar(query);
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
      <DialogContent className="sm:max-w-md">
        <DialogHeader>
          <DialogTitle>{documento.nome}</DialogTitle>
          <DialogDescription>
            Pesquise o aluno pelo número de processo ou nome e prima{' '}
            <kbd className="rounded border border-slate-200 bg-slate-100 px-1 py-0.5 font-mono text-xs">
              Enter
            </kbd>{' '}
            para procurar.
          </DialogDescription>
        </DialogHeader>

        <div className="space-y-6">
          {/* Pesquisa */}
          <FieldGroup>
            <Field>
              <FieldLabel htmlFor="pesquisa">
                Aluno <span className="text-red-500">*</span>
              </FieldLabel>
              <div className="flex gap-2">
                <div className="relative flex-1">
                  {searching ? (
                    <Loader2 className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 animate-spin text-muted-foreground" />
                  ) : (
                    <Search className="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                  )}
                  <Input
                    id="pesquisa"
                    ref={inputRef}
                    placeholder="Nº de processo ou nome…"
                    className="pr-8 pl-9"
                    value={query}
                    onChange={(e) => {
                      setQuery(e.target.value);
                      if (!e.target.value) handleLimparPesquisa();
                    }}
                    onKeyDown={handleKeyDown}
                    autoComplete="off"
                  />
                  {query && (
                    <button
                      type="button"
                      onClick={handleLimparPesquisa}
                      className="absolute top-1/2 right-3 -translate-y-1/2 text-muted-foreground hover:text-foreground"
                    >
                      <X className="h-3.5 w-3.5" />
                    </button>
                  )}
                </div>
                <Button
                  type="button"
                  variant="outline"
                  onClick={() => pesquisar(query)}
                  disabled={searching || query.trim().length < 3}
                >
                  {searching ? (
                    <Loader2 className="h-4 w-4 animate-spin" />
                  ) : (
                    <Search className="h-4 w-4" />
                  )}
                </Button>
              </div>
              <FieldDescription>
                Escreva pelo menos 3 caracteres e prima <strong>Enter</strong>{' '}
                ou clique em <Search className="inline h-3 w-3" /> para
                pesquisar.
              </FieldDescription>
            </Field>
          </FieldGroup>

          {/* Resultados */}
          {resultados.length > 0 && (
            <div className="space-y-2">
              <p className="text-xs font-medium tracking-wide text-muted-foreground uppercase">
                {resultados.length === 1
                  ? '1 aluno encontrado'
                  : `${resultados.length} alunos encontrados`}{' '}
                — seleccione um
              </p>
              <div className="max-h-48 space-y-1.5 overflow-y-auto pr-0.5">
                {resultados.map((aluno) => (
                  <AlunoItem
                    key={aluno.id}
                    aluno={aluno}
                    seleccionado={alunoSeleccionado}
                    onSeleccionar={handleSeleccionarAluno}
                  />
                ))}
              </div>
            </div>
          )}

          {/* Não encontrado */}
          {notFound && queryActual && (
            <div className="flex items-center gap-2.5 rounded-lg border border-orange-100 bg-orange-50 px-4 py-3">
              <AlertCircle className="h-4 w-4 shrink-0 text-orange-500" />
              <div>
                <p className="text-sm font-medium text-orange-800">
                  Nenhum aluno encontrado
                </p>
                <p className="text-xs text-orange-600">
                  Não foi encontrado nenhum aluno para{' '}
                  <span className="font-medium">"{queryActual}"</span>.
                  Verifique o número de processo ou o nome.
                </p>
              </div>
            </div>
          )}

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
                      <SelectValue placeholder="Seleccione a classe…" />
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
                  <FieldDescription>
                    Escolha a classe do documento
                  </FieldDescription>
                </Field>

                <Field>
                  <FieldLabel htmlFor="efeito">Efeito</FieldLabel>
                  <Input
                    id="efeito"
                    placeholder="Ex: de frequência…"
                    value={efeito}
                    onChange={(e) => setEfeito(e.target.value)}
                  />
                  <FieldDescription>Opcional</FieldDescription>
                </Field>
              </div>
            </FieldGroup>
          )}

          {/* Erro */}
          {erro && (
            <div className="flex items-center gap-2.5 rounded-lg border border-red-100 bg-red-50 px-4 py-3">
              <AlertCircle className="h-4 w-4 shrink-0 text-red-500" />
              <p className="text-sm text-red-700">{erro}</p>
            </div>
          )}

          {/* Botões */}
          <div className="flex justify-end gap-3">
            <Button variant="outline" onClick={handleClose} disabled={loading}>
              Cancelar
            </Button>
            <Button
              onClick={handleSubmit}
              disabled={loading || !alunoSeleccionado}
              className="gap-2"
            >
              {loading ? (
                <Loader2 className="mr-2 size-4 animate-spin" />
              ) : (
                <Download className="mr-2 size-4" />
              )}
              Exportar PDF
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
