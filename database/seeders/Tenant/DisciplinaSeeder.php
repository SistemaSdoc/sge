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
        Disciplina::updateOrCreate(['sigla' => 'L.Inglesa'], [
            'nome' => 'Inglês',
            'componente' => 'sociocultural',
            'sigla' => 'L.Inglesa',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'MAT'], [
            'nome' => 'Matemática',
            'componente' => 'cientifica',
            'sigla' => 'MAT',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'TIC'], [
            'nome' => 'Tecnologias de Informação e Comunicação',
            'componente' => 'tecnica',
            'sigla' => 'TIC',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'TLP'], [
            'nome' => 'Técnicas e Linguagem de programação',
            'componente' => 'tecnica',
            'sigla' => 'TLP',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'BD'], [
            'nome' => 'Base de Dados',
            'componente' => 'cientifica',
            'sigla' => 'BD',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'L.Portuguesa'], [
            'nome' => 'Lingua Portuguesa',
            'componente' => 'sociocultural',
            'sigla' => 'L.Portuguesa',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'OAE'], [
            'nome' => 'Organização e Administração de Empresas',
            'componente' => 'tecnica',
            'sigla' => 'OAE',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'FAI'], [
            'nome' => 'Formação de Atitudes Integradoras',
            'componente' => 'sociocultural',
            'sigla' => 'FAI',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'ND'], [
            'nome' => 'Nocões de Direito',
            'componente' => 'cientifica',
            'sigla' => 'ND',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'RC'], [
            'nome' => 'Redes de Computadores',
            'componente' => 'tecnica',
            'sigla' => 'RC',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'IAG'], [
            'nome' => 'Informática Aplicada à Gestão',
            'componente' => 'tecnica',
            'sigla' => 'IAG',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'Ed.Fisica'], [
            'nome' => 'Educação Física',
            'componente' => 'sociocultural',
            'sigla' => 'Ed.Fisica',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'SI'], [
            'nome' => 'Sistemas de Informação',
            'componente' => 'tecnica',
            'sigla' => 'SI',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'EMPREEN'], [
            'nome' => 'Empreendedorismo',
            'componente' => 'tecnica',
            'sigla' => 'EMPREEN',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'PT'], [
            'nome' => 'Projeto Tecnológico',
            'componente' => 'tecnica',
            'sigla' => 'PT',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'IMEI'], [
            'nome' => 'Instalação e Manutenção de Equipamentos Informáticos',
            'componente' => 'tecnica',
            'sigla' => 'IMEI',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'PAP'], [
            'nome' => 'Prova de Aptidão Profissional',
            'componente' => 'tecnica',
            'sigla' => 'PAP',
        ]);

        Disciplina::updateOrCreate(['sigla' => 'ECS'], [
            'nome' => 'Estágio Curricular Supervisionado',
            'componente' => 'tecnica',
            'sigla' => 'ECS',
        ]);
    }
}
