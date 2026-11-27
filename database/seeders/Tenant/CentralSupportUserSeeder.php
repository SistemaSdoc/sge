<?php

namespace Database\Seeders\Tenant;

use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class CentralSupportUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $superAdminRole = Role::findOrCreate('SuperAdmin', 'tenant');
        $directorRole = Role::findOrCreate('Director', 'tenant');

        $supportUser = User::firstOrCreate(
            ['email' => User::CENTRAL_SUPPORT_EMAIL],
            [
                'id' => (string) Str::uuid7(),
                'nome' => 'Suporte Central',
                'password' => Hash::make(Str::random(64)),
            ],
        );

        $instituicaoId = Instituicao::query()->value('id');

        if ($instituicaoId && (string) $supportUser->instituicao_id !== (string) $instituicaoId) {
            $supportUser->forceFill(['instituicao_id' => $instituicaoId])->save();
        }

        $supportUser->syncRoles([$superAdminRole, $directorRole]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
