<?php

namespace App\Imports;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UsersImport implements ToModel, WithHeadingRow
{
    /**
     * @return Model|null
     */
    public function model(array $row)
    {
        return new User([
            'nome' => $row['nome'],
            'email' => $row['email'],
            'bi' => $row['bi'],
            'telefone' => $row['telefone'],
            'password' => Hash::make($row['password']),
        ]);
    }
}
