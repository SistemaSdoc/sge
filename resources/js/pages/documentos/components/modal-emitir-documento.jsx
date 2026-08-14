import { AlertCircle, Download, Loader2, Search, X } from 'lucide-react';
import { useRef, useState } from 'react';

import { Alert, AlertDescription } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';

import { usePesquisaAlunos } from '../hooks/use-pesquisa-alunos';

// ─── Item da lista ────────────────────────────────────────────────────────────

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

// ─── Modal ────────────────────────────────────────────────────────────────────

export function ModalEmitirDocumento({ documento, classes, open, onClose }) {
  const [query, setQuery] = useState('');
  const [alunoSeleccionado, setAlunoSeleccionado] = useState(null);
  const [classeId, setClasseId] = useState('');
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

  function handleSubmit() {
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
    });

    setLoading(true);

    try {
      window.open(
        `/dashboard/documentos/exportar?${params.toString()}`,
        '_blank',
      );
      handleClose();
    } catch {
      setErro('Ocorreu um erro ao abrir o PDF do documento.');
    } finally {
      setLoading(false);
    }
  }

  if (!documento) {
    return null;
  }

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

        <div className="space-y-4 pt-2">
          {/* Pesquisa */}
          <div className="space-y-1.5">
            <Label htmlFor="pesquisa">Aluno</Label>

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

                    if (!e.target.value) {
                      handleLimparPesquisa();
                    }
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

            <p className="text-xs text-muted-foreground">
              Escreva pelo menos 3 caracteres e prima <strong>Enter</strong> ou
              clique em <Search className="inline h-3 w-3" /> para pesquisar.
            </p>
          </div>

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

          {/* Selector de classe */}
          {requerSelectClasse && alunoSeleccionado && (
            <div className="space-y-1.5">
              <Label htmlFor="classe">Classe</Label>
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
                      {(c.curso ? `${c.curso} · ` : '') + (c.classe ?? c.nome)}
                    </SelectItem>
                  ))}
                </SelectContent>
              </Select>
            </div>
          )}

          {/* Erro */}
          {erro && (
            <Alert variant="destructive" className="py-2">
              <AlertCircle className="h-4 w-4" />
              <AlertDescription>{erro}</AlertDescription>
            </Alert>
          )}

          {/* Acções */}
          <div className="flex justify-end gap-2 pt-2">
            <Button variant="outline" onClick={handleClose} disabled={loading}>
              Cancelar
            </Button>
            <Button
              onClick={handleSubmit}
              disabled={loading || !alunoSeleccionado}
              className="gap-2"
            >
              {loading ? (
                <Loader2 className="h-4 w-4 animate-spin" />
              ) : (
                <Download className="h-4 w-4" />
              )}
              Exportar PDF
            </Button>
          </div>
        </div>
      </DialogContent>
    </Dialog>
  );
}
