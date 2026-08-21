<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Disciplina;
use Illuminate\Database\Seeder;

class DisciplinaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Disciplina::create([
            'nome' => 'Inglês',
            'componente' => 'sociocultural',
            'sigla' => 'L.Inglesa',
        ]);

        Disciplina::create([
            'nome' => 'Matemática',
            'componente' => 'cientifica',
            'sigla' => 'MAT',
        ]);

        Disciplina::create([
            'nome' => 'Tecnologias de Informação e Comunicação',
            'componente' => 'tecnica',
            'sigla' => 'TIC',
        ]);

        Disciplina::create([
            'nome' => 'Técnicas e Linguagem de programação',
            'componente' => 'tecnica',
            'sigla' => 'TLP',
        ]);

        Disciplina::create([
            'nome' => 'Base de Dados',
            'componente' => 'cientifica',
            'sigla' => 'BD',
        ]);

        Disciplina::create([
            'nome' => 'Lingua Portuguesa',
            'componente' => 'sociocultural',
            'sigla' => 'L.Portuguesa',
        ]);

        Disciplina::create([
            'nome' => 'Organização e Administração de Empresas',
            'componente' => 'tecnica',
            'sigla' => 'OAE',
        ]);

        Disciplina::create([
            'nome' => 'Formação de Atitudes Integradoras',
            'componente' => 'sociocultural',
            'sigla' => 'FAI',
        ]);

        Disciplina::create([
            'nome' => 'Nocões de Direito',
            'componente' => 'cientifica',
            'sigla' => 'ND',
        ]);

        Disciplina::create([
            'nome' => 'Redes de Computadores',
            'componente' => 'tecnica',
            'sigla' => 'RC',
        ]);

        Disciplina::create([
            'nome' => 'Informática Aplicada à Gestão',
            'componente' => 'tecnica',
            'sigla' => 'IAG',
        ]);

        Disciplina::create([
            'nome' => 'Educação Física',
            'componente' => 'sociocultural',
            'sigla' => 'Ed.Fisica',
        ]);

        Disciplina::create([
            'nome' => 'Sistemas de Informação',
            'componente' => 'tecnica',
            'sigla' => 'SI',
        ]);

        Disciplina::create([
            'nome' => 'Empreendedorismo',
            'componente' => 'tecnica',
            'sigla' => 'EMPREEN',
        ]);

        Disciplina::create([
            'nome' => 'Projeto Tecnológico',
            'componente' => 'tecnica',
            'sigla' => 'PT',
        ]);

        Disciplina::create([
            'nome' => 'Instalação e Manutenção de Equipamentos Informáticos',
            'componente' => 'tecnica',
            'sigla' => 'IMEI',
        ]);

        Disciplina::create([
            'nome' => 'Prova de Aptidão Profissional',
            'componente' => 'tecnica',
            'sigla' => 'PAP',
        ]);

        Disciplina::create([
            'nome' => 'Estágio Curricular Supervisionado',
            'componente' => 'tecnica',
            'sigla' => 'ECS',
        ]);
    }
}
