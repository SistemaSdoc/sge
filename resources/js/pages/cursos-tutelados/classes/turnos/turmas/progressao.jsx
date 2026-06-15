
'use client'

import { useState, useMemo } from 'react'
import { router } from '@inertiajs/react'
import { Loader2, CheckCircle2, XCircle, AlertTriangle, ChevronRight, Clock, AlertCircle } from 'lucide-react'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Card, CardContent, CardHeader, CardTitle, CardDescription } from '@/components/ui/card'
import { Select, SelectContent, SelectGroup, SelectItem, SelectLabel, SelectTrigger, SelectValue } from '@/components/ui/select'
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog'

const situacaoConfig = {
  transita: {
    label: 'Aprovado',
    icon: <CheckCircle2 className="size-3" />,
    className: 'bg-green-100 text-green-700 border-green-200',
  },
  aprovado: {
    label: 'Aprovado',
    icon: <CheckCircle2 className="size-3" />,
    className: 'bg-green-100 text-green-700 border-green-200',
  },
  transita_com_deficiencia: {
    label: 'Transita c/ Deficiência',
    icon: <AlertTriangle className="size-3" />,
    className: 'bg-yellow-100 text-yellow-700 border-yellow-200',
  },
  recurso: {
    label: 'Recurso',
    icon: <AlertCircle className="size-3" />,
    className: 'bg-orange-100 text-orange-700 border-orange-200',
  },
  EEF: {
    label: 'EEF',
    icon: <XCircle className="size-3" />,
    className: 'bg-red-100 text-red-700 border-red-200',
  },
  incompleto: {
    label: 'Incompleto',
    icon: <Clock className="size-3" />,
    className: 'bg-gray-100 text-gray-600 border-gray-200',
  },
}

function SituacaoBadge({ situacao }) {
  const config = situacaoConfig[situacao] ?? situacaoConfig.incompleto
  return (
    <span className={`inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium border ${config.className}`}>
      {config.icon} {config.label}
    </span>
  )
}

export default function ProgressaoPage({ alunos, turma, total, resumo, resultado, turmas }) {
  const [turmaDestinoId, setTurmaDestinoId] = useState('')
  const [anoLectivo, setAnoLectivo] = useState(String(new Date().getFullYear() + 1))
  const [dialogOpen, setDialogOpen] = useState(false)
  const [isPending, setIsPending] = useState(false)

  const handleExecutar = () => {
    setIsPending(true)
    router.post(window.location.pathname, {
      turma_destino_id: turmaDestinoId,
      ano_lectivo: Number(anoLectivo),
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setDialogOpen(false)
      },
      onError: (errors) => {
        alert('Erro ao executar progressão: ' + Object.values(errors).flat().join(', '))
        setDialogOpen(false)
      },
      onFinish: () => setIsPending(false),
    })
  }

  if (resultado)
    return (
      <div className="max-w-2xl mx-auto px-6 py-10 space-y-6">
        <Card>
          <CardHeader>
            <CardTitle className="flex items-center gap-2 text-green-600">
              <CheckCircle2 className="size-5" />
              Progressão executada com sucesso
            </CardTitle>
          </CardHeader>
          <CardContent className="space-y-5">
            {[
              { key: 'transitados', label: 'Transitados', icon: <CheckCircle2 className="size-4 text-green-500" /> },
              { key: 'transita_com_deficiencia', label: 'Transitam com Deficiência', icon: <AlertTriangle className="size-4 text-yellow-500" /> },
              { key: 'recurso', label: 'Recurso', icon: <AlertCircle className="size-4 text-orange-500" /> },
              { key: 'retidos', label: 'Retidos (Reprovados)', icon: <XCircle className="size-4 text-red-500" /> },
              { key: 'incompletos', label: 'Incompletos', icon: <Clock className="size-4 text-gray-500" /> },
            ].map(({ key, label, icon }) =>
              resultado[key]?.length > 0 && (
                <div key={key}>
                  <p className="font-medium text-sm text-muted-foreground mb-2">
                    {label} ({resultado[key].length})
                  </p>
                  <ul className="space-y-1">
                    {resultado[key].map((item, i) => (
                      <li key={i} className="flex items-center gap-2 text-sm">
                        {icon}
                        {typeof item === 'string' ? item : item.nome}
                      </li>
                    ))}
                  </ul>
                </div>
              )
            )}
            <Button onClick={() => window.history.back()} className="w-full mt-4">
              Voltar
            </Button>
          </CardContent>
        </Card>
      </div>
    )

  return (
    <div className="max-w-4xl mx-auto px-6 py-10 space-y-6">
      {/* Cabeçalho */}
      <div>
        <h1 className="text-2xl font-bold">Progressão de Alunos</h1>
        <p className="text-muted-foreground text-sm mt-1">
          Turma: <span className="font-medium text-foreground">{turma}</span>
        </p>
      </div>

      {/* Resumo */}
      <div className="grid grid-cols-2 md:grid-cols-4 gap-4">
        {[
          { label: 'Transitam', value: resumo?.transitar ?? 0, className: 'text-green-600' },
          { label: 'Recurso', value: resumo?.aguardar_recurso ?? 0, className: 'text-orange-600' },
          { label: 'Retidos', value: resumo?.reter ?? 0, className: 'text-red-600' },
          { label: 'Incompleto', value: resumo?.incompleto ?? 0, className: 'text-gray-600' },
        ].map(({ label, value, className }) => (
          <Card key={label}>
            <CardContent className="pt-6">
              <p className={`text-3xl font-bold ${className}`}>{value}</p>
              <p className="text-sm text-muted-foreground mt-1">{label}</p>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Tabela de alunos */}
      <Card>
        <CardHeader>
          <CardTitle>Lista de Alunos</CardTitle>
          <CardDescription>Pré-visualização antes de executar a progressão</CardDescription>
        </CardHeader>
        <CardContent>
          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>#</TableHead>
                <TableHead>Nome</TableHead>
                <TableHead>Matrícula</TableHead>
                <TableHead>Situação</TableHead>
                <TableHead>Mensagem</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {alunos?.map((aluno, i) => (
                <TableRow key={aluno.aluno_id}>
                  <TableCell>{i + 1}</TableCell>
                  <TableCell className="font-medium">{aluno.nome}</TableCell>
                  <TableCell className="text-muted-foreground">{aluno.matricula}</TableCell>
                  <TableCell><SituacaoBadge situacao={aluno.situacao} /></TableCell>
                  <TableCell className="text-sm text-muted-foreground">{aluno.mensagem}</TableCell>
                </TableRow>
              ))}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Formulário de execução */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <AlertTriangle className="size-5 text-yellow-500" />
            Executar Progressão
          </CardTitle>
          <CardDescription>
            Defina a turma destino e o ano lectivo para os alunos que transitam.
          </CardDescription>
        </CardHeader>
        <CardContent className="space-y-4">
          <div className="space-y-1">
            <label className="text-sm font-medium">Turma Destino</label>
            <Select value={turmaDestinoId} onValueChange={setTurmaDestinoId}>
              <SelectTrigger className="w-full">
                <SelectValue placeholder="Selecione a turma destino" />
              </SelectTrigger>
              <SelectContent>
                <SelectGroup>
                  <SelectLabel>Turmas disponíveis</SelectLabel>
                  {turmas?.map((t) => (
                    <SelectItem key={t.id} value={t.id}>
                      {t.nome} — {t.classe} ({t.turno})
                    </SelectItem>
                  ))}
                </SelectGroup>
              </SelectContent>
            </Select>
          </div>

          <div className="space-y-1">
            <label className="text-sm font-medium">Ano Lectivo</label>
            <Input
              type="number"
              placeholder="Ex: 2026"
              value={anoLectivo}
              onChange={(e) => setAnoLectivo(e.target.value)}
            />
          </div>

          <Dialog open={dialogOpen} onOpenChange={setDialogOpen}>
            <DialogTrigger asChild>
              <Button className="w-full" disabled={!turmaDestinoId || !anoLectivo}>
                Executar Progressão <ChevronRight className="size-4 ml-1" />
              </Button>
            </DialogTrigger>
            <DialogContent>
              <DialogHeader>
                <DialogTitle>Confirmar Progressão</DialogTitle>
                <DialogDescription asChild>
                  <div className="space-y-2 text-sm text-muted-foreground">
                    <p>Esta operação vai processar <strong>{total} alunos</strong>:</p>
                    <ul className="space-y-1 pl-4 list-disc">
                      <li><strong>{resumo?.transitar ?? 0}</strong> transitam para a turma destino</li>
                      <li><strong>{resumo?.aguardar_recurso ?? 0}</strong> vão a recurso — ficam na turma actual</li>
                      <li><strong>{resumo?.reter ?? 0}</strong> reprovados — repetem o ano</li>
                      <li><strong>{resumo?.incompleto ?? 0}</strong> incompletos — notas em falta</li>
                    </ul>
                    <p className="text-orange-600 font-medium">Esta operação não pode ser desfeita.</p>
                  </div>
                </DialogDescription>
              </DialogHeader>
              <DialogFooter>
                <Button variant="outline" onClick={() => setDialogOpen(false)}>
                  Cancelar
                </Button>
                <Button onClick={handleExecutar} disabled={isPending}>
                  {isPending ? (
                    <><Loader2 className="animate-spin size-4 mr-2" /> A executar...</>
                  ) : 'Confirmar'}
                </Button>
              </DialogFooter>
            </DialogContent>
          </Dialog>
        </CardContent>
      </Card>
    </div>
  )
}