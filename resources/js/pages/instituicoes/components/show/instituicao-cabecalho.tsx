import { router } from "@inertiajs/react";
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from "@/components/ui/dropdown-menu"
import { Card, CardContent } from "@/components/ui/card"
import { Button } from "@/components/ui/button"
import { Minus, MoreHorizontalIcon } from "lucide-react"


interface Instituicao {
  id: number;
  nome: string;
  sigla: string;
  email: string;
  telefone: string;
  endereco: string;
  logo: string | null;
}

export function InstituicaoCabecalho({ data, storageUrl }: { data: Instituicao; storageUrl: string }) {
  return (
    <Card className="overflow-hidden pt-0!">
      <div className="relative flex items-end w-full h-56 bg-muted overflow-hidden">
        {data.logo ? (
          <img
            src={`${storageUrl}/${data.logo}`}
            alt={`Logo ${data.nome}`}
            sizes="(max-width: 768px) 100vw, 1000px"
            loading="lazy"
            className="absolute inset-0 w-full h-full object-cover object-center"
          />
        ) : (
          <div className="absolute inset-0 w-full h-full bg-gradient-to-br from-muted/60 to-muted/40" />
        )}
        {/* overlay: z-10 para ficar acima da imagem */}
        <div className="absolute inset-0 z-10 bg-black/50" />

        {/* conteúdo: z-20 para ficar acima do overlay */}
        <div className="relative z-20 flex items-end justify-between w-full p-6">
          <div className="space-y-2 text-white">
            <h1 className="text-2xl font-semibold md:text-3xl">
              {data.nome}
            </h1>
            <p className="text-sm opacity-90">
              {data.sigla} - {data.email}
            </p>
          </div>

          <DropdownMenu>
            <DropdownMenuTrigger asChild>
              <Button variant="ghost" size="icon" className="text-white hover:bg-white/20">
                <MoreHorizontalIcon />
              </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end">
              <DropdownMenuItem onClick={() => router.visit(`/instituicoes/${data.id}/edit`)}>
                Editar
              </DropdownMenuItem>
            </DropdownMenuContent>
          </DropdownMenu>
        </div>
      </div>


      <CardContent className="grid grid-cols-1 gap-6 py-6 md:grid-cols-3">
        <div>
          <p className="text-sm text-muted-foreground">Telefone</p>
          <p className="font-medium">{data.telefone || <Minus size={15} className="text-muted-foreground" />}</p>
        </div>

        <div>
          <p className="text-sm text-muted-foreground">Endereço</p>
          <p className="font-medium">{data.endereco || <Minus size={15} className="text-muted-foreground" />}</p>
        </div>
      </CardContent>
    </Card>
  )
}