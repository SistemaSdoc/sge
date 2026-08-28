import { useId, useState } from 'react';
import { router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
  Card,
  CardAction,
  CardContent,
  CardDescription,
  CardHeader,
  CardTitle,
} from '@/components/ui/card';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { FileText, MoreHorizontalIcon } from 'lucide-react';
import {
  Dialog,
  DialogContent,
  DialogHeader,
  DialogTitle,
  DialogFooter,
} from '@/components/ui/dialog';
import { uploadCriteriosPap } from '@/actions/App/Http/Controllers/Tenant/CursoTuteladoController';

export function TabCriteriosPap({ instituicaoId, cursoTuteladoId, criteriosPapUrl, manualPtUrl, estruturaTrabalhoPapUrl, can }) {
  const criteriosId = useId();
  const manualId = useId();
  const estruturaTrabalhoPapId = useId();
  const [modalAberto, setModalAberto] = useState(false);
  const [ficheiroCriterios, setFicheiroCriterios] = useState(null);
  const [ficheiroManual, setFicheiroManual] = useState(null);
  const [ficheiroEstruturaTrabalhoPap, setFicheiroEstruturaTrabalhoPap] = useState(null);
  const [uploading, setUploading] = useState(false);

  const faltaCriterios = !criteriosPapUrl;
  const faltaManual = !manualPtUrl;
  const faltaEstruturaTrabalhoPap = !estruturaTrabalhoPapUrl;
  const algumDocumento = !!(criteriosPapUrl || manualPtUrl || estruturaTrabalhoPapUrl);
  const todosCarregados = !!(criteriosPapUrl && manualPtUrl && estruturaTrabalhoPapUrl);

  // Botão válido se os ficheiros em falta estiverem seleccionados, e pelo menos um seleccionado
  const uploadValido =
    (!faltaCriterios || ficheiroCriterios) &&
    (!faltaManual || ficheiroManual) &&
    (!faltaEstruturaTrabalhoPap || ficheiroEstruturaTrabalhoPap) &&
    !!(ficheiroCriterios || ficheiroManual || ficheiroEstruturaTrabalhoPap);

  const handleFecharModal = () => {
    setModalAberto(false);
    setFicheiroCriterios(null);
    setFicheiroManual(null);
    setFicheiroEstruturaTrabalhoPap(null);
  };

  const handleUpload = () => {
    if (!uploadValido) return;

    const payload = {};
    if (ficheiroCriterios) payload.criterios_pap = ficheiroCriterios;
    if (ficheiroManual) payload.manual_pt = ficheiroManual;
    if (ficheiroEstruturaTrabalhoPap) payload.estrutura_trabalho_pap = ficheiroEstruturaTrabalhoPap;

    setUploading(true);
    router.post(
      uploadCriteriosPap({
        instituicao: params.instituicao.id,
        cursoTutelado: params.cursoTutelado.id,
      }).url,
      payload,
      {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
          console.log('chegou no sucess');
          setUploading(false);
          handleFecharModal();
          router.reload({ only: ['cursoTutelado'] });
        },
        onError: () => {
          console.log('chegou no sucess');

          setUploading(false);
        },
      },
    );
  };

  const documentos = [
    { label: 'Critérios PAP.pdf', url: criteriosPapUrl },
    { label: 'Manual PT.pdf', url: manualPtUrl },
    { label: 'Estrutura do Trabalho PAP.pdf', url: estruturaTrabalhoPapUrl },
  ];

  return (
    <>
      <Card className="gap-0">
        <CardHeader className="border-b">
          <CardTitle>Critérios PAP</CardTitle>
          <CardDescription>
            Documentos PDF com os critérios de aprovação do tema e o manual PT
            para este curso.
          </CardDescription>
          {can?.uploadCriteriosPap && (
            <CardAction>
              <Button onClick={() => setModalAberto(true)}>
                {todosCarregados ? 'Substituir' : 'Adicionar'}
              </Button>
            </CardAction>
          )}
        </CardHeader>

        <CardContent className="p-0!">
          <Table>
            <TableHeader>
              <TableRow className="bg-muted/72">
                <TableHead className="px-4">Documento</TableHead>
                <TableHead className="px-4">Estado</TableHead>
                <TableHead className="px-4 text-right">Acções</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {algumDocumento ? (
                documentos.map(({ label, url }) => (
                  <TableRow key={label}>
                    <TableCell className="px-4">
                      <div className="flex items-center gap-2">
                        <FileText
                          className={`size-4 ${url ? 'text-red-500' : 'text-muted-foreground'}`}
                        />
                        <span className="font-medium">{label}</span>
                      </div>
                    </TableCell>
                    <TableCell className="px-4">
                      <span className="inline-flex items-center px-2 text-xs font-medium">
                        {url ? 'Disponível' : 'Não carregado'}
                      </span>
                    </TableCell>
                    <TableCell className="px-4 text-right">
                      {url ? (
                        <DropdownMenu>
                          <DropdownMenuTrigger asChild>
                            <Button
                              variant="ghost"
                              size="icon"
                              className="size-8"
                            >
                              <MoreHorizontalIcon />
                              <span className="sr-only">Abrir menu</span>
                            </Button>
                          </DropdownMenuTrigger>
                          <DropdownMenuContent align="end">
                            <DropdownMenuItem asChild>
                              <a
                                href={url}
                                target="_blank"
                                rel="noopener noreferrer"
                              >
                                Visualizar
                              </a>
                            </DropdownMenuItem>
                          </DropdownMenuContent>
                        </DropdownMenu>
                      ) : (
                        <span className="text-xs text-muted-foreground">—</span>
                      )}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell
                    colSpan={3}
                    className="px-4 py-8 text-center text-muted-foreground"
                  >
                    Nenhum documento carregado ainda.
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </CardContent>
      </Card>

      {/* Modal de upload */}
      <Dialog
        open={modalAberto}
        onOpenChange={(v) => {
          if (!v) handleFecharModal();
          else setModalAberto(true);
        }}
      >
        <DialogContent className="sm:max-w-md">
          <DialogHeader>
            <DialogTitle>
              {todosCarregados
                ? 'Substituir Documentos'
                : 'Adicionar Documentos'}
            </DialogTitle>
          </DialogHeader>

          <div className="space-y-4 py-2">
            <div className="space-y-2">
              <Label htmlFor={criteriosId}>
                Critérios PAP (PDF)
                {!faltaCriterios && (
                  <span className="font-normal text-muted-foreground">
                    {' '}
                    — opcional
                  </span>
                )}
              </Label>
              <Input
                id={criteriosId}
                type="file"
                accept=".pdf"
                onChange={(e) =>
                  setFicheiroCriterios(e.target.files?.[0] ?? null)
                }
                className="p-0 pr-3 text-muted-foreground italic file:mr-3 file:h-full file:border-0 file:border-r file:border-solid file:border-input file:bg-transparent file:px-3 file:text-sm file:font-medium file:text-foreground file:not-italic"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={manualId}>
                Manual PT (PDF)
                {!faltaManual && (
                  <span className="font-normal text-muted-foreground">
                    {' '}
                    — opcional
                  </span>
                )}
              </Label>
              <Input
                id={manualId}
                type="file"
                accept=".pdf"
                onChange={(e) => setFicheiroManual(e.target.files?.[0] ?? null)}
                className="p-0 pr-3 text-muted-foreground italic file:mr-3 file:h-full file:border-0 file:border-r file:border-solid file:border-input file:bg-transparent file:px-3 file:text-sm file:font-medium file:text-foreground file:not-italic"
              />
            </div>

            <div className="space-y-2">
              <Label htmlFor={estruturaTrabalhoPapId}>
                Estrutura do Trabalho PAP (PDF){!faltaEstruturaTrabalhoPap && <span className="text-muted-foreground font-normal"> — opcional</span>}
              </Label>
              <Input
                id={estruturaTrabalhoPapId}
                type="file"
                accept=".pdf"
                onChange={(e) => setFicheiroEstruturaTrabalhoPap(e.target.files?.[0] ?? null)}
                className="text-muted-foreground file:border-input file:text-foreground p-0 pr-3 italic file:mr-3 file:h-full file:border-0 file:border-r file:border-solid file:bg-transparent file:px-3 file:text-sm file:font-medium file:not-italic"
              />
            </div>
          </div>

          <DialogFooter>
            <Button variant="outline" onClick={handleFecharModal}>
              Cancelar
            </Button>
            <Button
              onClick={handleUpload}
              disabled={!uploadValido || uploading}
            >
              {uploading ? 'A carregar...' : 'Carregar'}
            </Button>
          </DialogFooter>
        </DialogContent>
      </Dialog>
    </>
  );
}
