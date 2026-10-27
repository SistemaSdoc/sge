<?php

namespace App\Exports\PautaFinal;

use App\Exports\PautaFinal\Classe10\GeradorPautaFinalClasse10;
use App\Exports\PautaFinal\Classe11\GeradorPautaFinalClasse11;
use App\Exports\PautaFinal\Classe12\EscritorPautaFinalClasse12;
use App\Exports\PautaFinal\Classe13\GeradorPautaFinalClasse13;
use App\Services\Tenant\Pauta\PautaFinalDisciplineMapper;
use InvalidArgumentException;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Encaminha a folha para o gerador específico da classe do template.
 */
final class PreenchedorPautaFinalTemplate
{
    /**
     * @param  array<string, string|null>  $cabecalho
     * @param  list<array{id: string, name: string, sigla: string}>  $disciplinas
     * @param  list<array<string, mixed>>  $alunos
     */
    public function __construct(
        private readonly string $caminhoTemplate,
        private readonly array $cabecalho,
        private readonly array $disciplinas,
        private readonly array $alunos,
    ) {}

    /**
     * Valida os dados e executa o gerador da classe correspondente.
     */
    public function preencher(Worksheet $folha): void
    {
        if ($this->disciplinas === []) {
            throw new InvalidArgumentException('A turma precisa de pelo menos uma disciplina para gerar a pauta final.');
        }

        match ($this->numeroClasse()) {
            10 => (new GeradorPautaFinalClasse10($this->cabecalho, $this->disciplinas, $this->alunos))->gerar($folha),
            11 => (new GeradorPautaFinalClasse11($this->cabecalho, $this->disciplinas, $this->alunos))->gerar($folha),
            12 => (new EscritorPautaFinalClasse12($this->cabecalho, $this->alunos))->escrever($folha),
            13 => (new GeradorPautaFinalClasse13(
                $this->cabecalho,
                $this->disciplinas,
                $this->alunos,
                new PautaFinalDisciplineMapper,
            ))->gerar($folha),
            default => throw new InvalidArgumentException('Só existem geradores de pauta final para a 10.ª, 11.ª, 12.ª e 13.ª classes.'),
        };
    }

    /**
     * Resolve a classe pelo caminho do template, usando o cabeçalho como fallback.
     */
    private function numeroClasse(): int
    {
        $caminho = str_replace('\\', '/', $this->caminhoTemplate);
        if (preg_match('~/([0-9]+)-classe/~', $caminho, $correspondencia) === 1) {
            return (int) $correspondencia[1];
        }

        if (preg_match('/([0-9]+)/', (string) ($this->cabecalho['class'] ?? ''), $correspondencia) === 1) {
            return (int) $correspondencia[1];
        }

        throw new InvalidArgumentException('Não foi possível identificar a classe do template final.');
    }
}
