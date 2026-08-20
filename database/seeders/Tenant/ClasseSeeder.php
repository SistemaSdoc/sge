<?php

namespace Database\Seeders\Tenant;

use App\Models\tenant\Classe;
use Illuminate\Database\Seeder;

class ClasseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $classes = [
            [
                'nome' => 'Pré-escolar',
                'nivel_ensino' => 'Pré-escolar',
                'emite_certificado' => true,
                'tipo_certificado' => 'Certificado de Conclusão do Pré-escolar',
                'ordem' => 0,
            ],
            [
                'nome' => '1ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 1,
            ],
            [
                'nome' => '2ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 2,
            ],
            [
                'nome' => '3ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 3,
            ],
            [
                'nome' => '4ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 4,
            ],
            [
                'nome' => '5ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 5,
            ],
            [
                'nome' => '6ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => true,
                'tipo_certificado' => 'Diploma do Ensino Básico',
                'ordem' => 6,
            ],
            [
                'nome' => '7ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 7,
            ],
            [
                'nome' => '8ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 8,
            ],
            [
                'nome' => '9ª',
                'nivel_ensino' => 'Ensino Básico',
                'emite_certificado' => true,
                'tipo_certificado' => 'Diploma do Ensino Básico',
                'ordem' => 9,
            ],
            [
                'nome' => '10ª',
                'nivel_ensino' => 'Ensino Secundário',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 10,
            ],
            [
                'nome' => '11ª',
                'nivel_ensino' => 'Ensino Secundário',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 11,
            ],
            [
                'nome' => '12ª',
                'nivel_ensino' => 'Ensino Secundário',
                'emite_certificado' => false,
                'tipo_certificado' => null,
                'ordem' => 12,
            ],
            [
                'nome' => '13ª',
                'nivel_ensino' => 'Ensino Secundário',
                'emite_certificado' => true,
                'tipo_certificado' => 'Diploma do Ensino Secundário',
                'ordem' => 13,
            ],
        ];

        foreach ($classes as $classe) {
            Classe::create($classe);
        }
    }
}
