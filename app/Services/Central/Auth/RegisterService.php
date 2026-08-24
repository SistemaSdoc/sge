<?php

namespace App\Services\Central\Auth;

use App\Models\Central\Tenant;
use App\Models\Tenant\Instituicao;
use App\Models\Tenant\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class RegisterService
{
    /**
     * Register a new institution with its owner.
     *
     * @throws ValidationException
     */
    public function register(array $data): string
    {
        $subdomain = $data['domain'];
        $baseDomain = env('APP_DOMAIN', 'localhost');
        $domain = "{$subdomain}.{$baseDomain}";

        $tenant = $this->createTenant($subdomain, $data['nome']);

        try {
            $this->createTenantDomain($tenant, $domain);

            $token = $this->createImpersonationToken($tenant, $data);

            return $this->buildRedirectUrl($domain, $token->token);
        } catch (\Throwable $e) {
            // $tenant->delete();
            throw $e;
        }
    }

    /**
     * Create a new tenant (institution).
     */
    private function createTenant(string $subdomain, string $name): Tenant
    {
        return Tenant::create([
            'id' => $subdomain,
            // ✅ Sem 'name' — vai ficar no JSON data
        ]);
    }

    /**
     * Create the domain entry for the tenant.
     */
    private function createTenantDomain(Tenant $tenant, string $domain): void
    {
        $tenant->domains()->create([
            'domain' => $domain,
        ]);
    }

    /**
     * Create the institution owner user and generate impersonation token.
     */
    private function createImpersonationToken(Tenant $tenant, array $data)
    {
        return $tenant->run(function () use ($data, $tenant) {
            $instituicao = $this->createTenantInstitution($data);

            // ✅ Guardar instituicao_id no tenant (central)
            $tenant->update(['instituicao_id' => $instituicao->id]);

            $user = $this->createTenantUser($data, $instituicao);

            return tenancy()->impersonate($tenant, $user->id, '/dashboard', 'tenant');
        });
    }

    /**
     * Create the institution within tenant context.
     */
    private function createTenantInstitution(array $data): Instituicao
    {
        return Instituicao::create([
            'nome' => $data['nome'],
            'sigla' => $data['sigla'],
            'tipo' => $data['tipo'],
            'email' => $data['user_email'],
            'telefone' => '923000000',
            'provincia' => 'Luanda',
            'endereco' => 'A definir',
            'descricao' => 'Instituição educativa',
            'status' => 1,
        ]);
    }

    /**
     * Create the institution owner user within tenant context.
     */
    private function createTenantUser(array $data, Instituicao $instituicao): User
    {
        $user = User::create([
            'nome' => $data['user_nome'],
            'email' => $data['user_email'],
            'password' => Hash::make($data['password']),
            'instituicao_id' => $instituicao->id,
        ]);

        $user->assignRole('Director');

        return $user;
    }

    /**
     * Build the complete redirect URL with port if necessary.
     */
    private function buildRedirectUrl(string $domain, string $token): string
    {
        $port = parse_url(config('app.url'), PHP_URL_PORT);
        $scheme = parse_url(config('app.url'), PHP_URL_SCHEME) ?? 'http';
        $portString = $port ? ":{$port}" : '';

        return "{$scheme}://{$domain}{$portString}/token/{$token}";
    }
}
