<?php

namespace App\Services\Tenant\Pauta;

use Illuminate\Support\Str;

final class PautaFinalDisciplineMapper
{
    /**
     * @var array<string, string>
     */
    private const DISCIPLINE_KEYS = [
        'LP' => 'lp',
        'LPORTUGUESA' => 'lp',
        'LINGUAPORTUGUESA' => 'lp',
        'PORT' => 'lp',
        'PORTCFD' => 'lp',
        'ING' => 'ing',
        'INGFRA' => 'ing',
        'INGFRACFD' => 'ing',
        'LINGLESA' => 'ing',
        'FAI' => 'fai',
        'FAICFD' => 'fai',
        'FORMAODEATITUDESINTEGRADORAS' => 'fai',
        'EDFIS' => 'edfisica',
        'EDFISICA' => 'edfisica',
        'EDUCACAOFISICA' => 'edfisica',
        'ND' => 'direito',
        'NDIREITO' => 'direito',
        'NOCOESDEDIREITO' => 'direito',
        'RC' => 'redes',
        'REDES' => 'redes',
        'REDESDECOMPUTADORES' => 'redes',
        'BD' => 'bd',
        'BDADOS' => 'bd',
        'BASEDADOS' => 'bd',
        'MAT' => 'mat',
        'MATEMATICA' => 'mat',
        'MAT2' => 'mat2',
        'MATEMAT2' => 'mat2',
        'OAE' => 'oae',
        'OAECFD' => 'oae',
        'ORGANIZACAOEADMINISTRACAODEEMPRESAS' => 'oae',
        'TIC' => 'tic',
        'TICCFD' => 'tic',
        'TECNOLOGIASDEINFORMACAOECOMUNICACAO' => 'tic',
        'IAG' => 'iag',
        'INFORMATICAAPLICADAAGESTAO' => 'iag',
        'TLP' => 'tlp',
        'TECNICASELINGUAGEMDEPROGRAMACAO' => 'tlp',
        'DIREITO' => 'direito12',
        'SI' => 'sitinfo',
        'SISTEMA' => 'sitinfo',
        'SITINFO' => 'sitinfo',
        'SITUACAOINFORMACIONAL' => 'sitinfo',
        'SISTEMASDEINFORMACAO' => 'sitinfo',
        'EMPREEN' => 'emp',
        'EMPREENDEDORISMO' => 'emp',
        'BDRC' => 'bdrc',
        'IMEI' => 'imei',
        'IMEICFD' => 'imei',
        'INSTALACAOEMANUTENCAODEEQUIPAMENTOSINFORMATICOS' => 'imei',
        'PT' => 'pt',
        'PJTEC' => 'pt',
        'PROJETOTECNOLOGICO' => 'pt',
        'PROJECTOTECNICO' => 'pt',
        'PAP' => 'pap',
    ];

    public function resolve(?string $sigla, ?string $name): ?string
    {
        foreach ([$sigla, $name] as $label) {
            if ($label === null || trim($label) === '') {
                continue;
            }

            $key = preg_replace('/[^A-Z0-9]/', '', Str::ascii(mb_strtoupper(trim($label), 'UTF-8'))) ?? '';

            if (isset(self::DISCIPLINE_KEYS[$key])) {
                return self::DISCIPLINE_KEYS[$key];
            }
        }

        return null;
    }
}
