<?php

namespace App\Services\Tenant\Pap\Trabalho;

use App\Models\Tenant\GrupoPap;
use App\Models\Tenant\TrabalhoPap;
use App\Models\Tenant\TrabalhoPapVersao;
use App\Models\Tenant\User;
use App\Notifications\Pap\TrabalhoSubmetidoAoTutorConfirmacaoNotification;
use App\Traits\NotificaGrupoPap;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

class SubmissaoTrabalhoPapService
{
    use NotificaGrupoPap;

    /**
     * Cria o registo do trabalho após a aprovação do tema.
     */
    public function inicializar(GrupoPap $grupoPap): TrabalhoPap
    {
        return TrabalhoPap::create([
            'grupo_pap_id' => $grupoPap->id,
            'status' => TrabalhoPap::STATUS_PENDENTE_ENTREGA,
        ]);
    }

    /**
     * Guarda uma nova versão do trabalho submetida pelo aluno.
     */
    public function submeter(
        TrabalhoPap $trabalho,
        User $user,
        UploadedFile $ficheiro
    ): TrabalhoPapVersao {
        if (! $trabalho->podeSerSubmetido()) {
            throw new \RuntimeException('O trabalho não pode ser submetido neste momento.');
        }

        return DB::transaction(function () use ($trabalho, $user, $ficheiro) {
            $numeroVersao = $trabalho->versoes()->max('numero_versao') + 1;

            Log::debug('SubmissaoTrabalhoPapService::submeter upload recebido', [
                'nome' => $ficheiro->getClientOriginalName(),
                'tamanho' => $ficheiro->getSize(),
                'erro' => $ficheiro->getError(),
                'valido' => $ficheiro->isValid(),
                'temporario' => $ficheiro->getRealPath(),
                'temporario_legivel' => $ficheiro->isReadable(),
                'private_root' => config('filesystems.disks.private.root'),
                'private_root_escrevivel' => is_writable(config('filesystems.disks.private.root')),
            ]);

            $caminho = $ficheiro->store(
                "trabalhos_pap/{$trabalho->grupo_pap_id}",
                'private'
            );

            $versao = TrabalhoPapVersao::create([
                'trabalho_pap_id' => $trabalho->id,
                'submetido_por_id' => $user->id,
                'numero_versao' => $numeroVersao,
                'caminho_ficheiro' => $caminho,
                'nome_original' => $ficheiro->getClientOriginalName(),
                'status_quando_submetido' => $trabalho->status,
            ]);

            $trabalho->update(['status' => TrabalhoPap::STATUS_EM_ANALISE_TUTOR]);

            $grupoPap = $trabalho->grupoPap->load('professor.user', 'alunos.user');

            $tutor = $grupoPap->professor?->user;

            $revisores = collect($tutor ? [$tutor] : []);

            $this->notificarTrabalhoSubmetido($grupoPap, $revisores);

            $alunos = $grupoPap->alunos->map->user->filter();

            if ($alunos->isNotEmpty()) {
                Notification::send(
                    $alunos,
                    new TrabalhoSubmetidoAoTutorConfirmacaoNotification($grupoPap)
                );
            }

            return $versao;
        });
    }
}
