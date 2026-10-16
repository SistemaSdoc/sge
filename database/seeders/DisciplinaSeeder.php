<?php

namespace Database\Seeders;

use App\Models\Central\Disciplina;
use Illuminate\Database\Seeder;

class DisciplinaSeeder extends Seeder
{
    public function run(): void
    {
        $disciplinas = [
            ['sigla' => 'L.Inglesa', 'nome' => 'Inglês', 'componente' => 'sociocultural'],
            ['sigla' => 'MAT', 'nome' => 'Matemática', 'componente' => 'cientifica'],
            ['sigla' => 'TIC', 'nome' => 'Tecnologias de Informação e Comunicação', 'componente' => 'tecnica'],
            ['sigla' => 'TLP', 'nome' => 'Técnicas e Linguagem de programação', 'componente' => 'tecnica'],
            ['sigla' => 'BD', 'nome' => 'Base de Dados', 'componente' => 'cientifica'],
            ['sigla' => 'L.Portuguesa', 'nome' => 'Lingua Portuguesa', 'componente' => 'sociocultural'],
            ['sigla' => 'OAE', 'nome' => 'Organização e Administração de Empresas', 'componente' => 'tecnica'],
            ['sigla' => 'FAI', 'nome' => 'Formação de Atitudes Integradoras', 'componente' => 'sociocultural'],
            ['sigla' => 'ND', 'nome' => 'Nocões de Direito', 'componente' => 'cientifica'],
            ['sigla' => 'RC', 'nome' => 'Redes de Computadores', 'componente' => 'tecnica'],
            ['sigla' => 'IAG', 'nome' => 'Informática Aplicada à Gestão', 'componente' => 'tecnica'],
            ['sigla' => 'Ed.Fisica', 'nome' => 'Educação Física', 'componente' => 'sociocultural'],
            ['sigla' => 'SI', 'nome' => 'Sistemas de Informação', 'componente' => 'tecnica'],
            ['sigla' => 'EMPREEN', 'nome' => 'Empreendedorismo', 'componente' => 'tecnica'],
            ['sigla' => 'PT', 'nome' => 'Projeto Tecnológico', 'componente' => 'tecnica'],
            ['sigla' => 'IMEI', 'nome' => 'Instalação e Manutenção de Equipamentos Informáticos', 'componente' => 'tecnica'],
            ['sigla' => 'ECS', 'nome' => 'Estágio Curricular Supervisionado', 'componente' => 'tecnica'],
        ];

        foreach ($disciplinas as $disciplina) {
            Disciplina::query()->updateOrCreate(
                ['sigla' => $disciplina['sigla']],
                [...$disciplina, 'carga_horaria' => 60, 'status' => 1],
            );
        }
    }
}
