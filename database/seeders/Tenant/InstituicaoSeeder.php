<?php

namespace Database\Seeders\Tenant;

use App\Models\tenant\Instituicao;
use Illuminate\Database\Seeder;

class InstituicaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Instituicao::create([
            'nome' => 'Instituto Médio Comercial De Luanda',
            'sigla' => 'IMCL',
            'tipo' => 'instituto',
            'email' => 'imcl@imcl.ao',
            'telefone' => '923000000',
            'provincia' => 'Luanda',
            'endereco' => 'Primeiro de Maio, Luanda, Angola',
            'descricao' => 'Instituto Médio Comercial De Luanda',
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);

        Instituicao::create([
            'nome' => 'Escola Secundária Modelo',
            'sigla' => 'ESM',
            'tipo' => 'colegio',
            'email' => 'geral@escola-modelo.ao',
            'telefone' => '923000002',
            'provincia' => 'Benguela',
            'endereco' => 'Benguela, Angola',
            'descricao' => 'Escola Secundária Modelo',
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);

        Instituicao::create([
            'nome' => 'Colegio Universitário de Angola',
            'sigla' => 'CUA',
            'tipo' => 'colegio',
            'email' => 'info@universidade-demo.ao',
            'telefone' => '923000003',
            'provincia' => 'Huambo',
            'endereco' => 'Huambo, Angola',
            'descricao' => 'Colegio Universitário de Angola',
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);

        Instituicao::create([
            'nome' => 'Complexo Escolar Luz da Sabedoria',
            'sigla' => 'lS',
            'tipo' => 'colegio',
            'email' => 'info@luzdasabedoria.ao',
            'telefone' => '923000003',
            'provincia' => 'luanda',
            'endereco' => 'Luanda, Samba',
            'descricao' => 'Complexo Escolar Luz da Sabedoria',
            'status' => 1, // 1 = ativo, 0 = inativo
        ]);
    }
}
