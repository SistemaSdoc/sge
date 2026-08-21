<?php

namespace App\Services\Central\Auth;

use App\Models\central\Tenant;
use App\Models\tenant\Instituicao;
use App\Models\tenant\User;
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

        $tenant = $this->createTenant($subdomain, $data['tenant_name']);

        try {
            $this->createTenantDomain($tenant, $domain);

            $token = $this->createImpersonationToken($tenant, $data);

            return $this->buildRedirectUrl($domain, $token->token);
        } catch (\Throwable $e) {
            $tenant->delete();
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
            'name' => $name,
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
            $user = $this->createTenantUser($data);

            return tenancy()->impersonate($tenant, $user->id, '/dashboard', 'tenant');
        });
    }

    /**
     * Create the institution owner user within tenant context.
     */
    private function createTenantUser(array $data): User
    {
        $instituicao = Instituicao::create([
            'nome' => $data['tenant_name'],
            'sigla' => strtoupper(substr($data['tenant_name'], 0, 3)),
            'tipo' => 'instituto',
            'email' => "director@{$data['domain']}.ao", // ← Director da instituição
            'telefone' => '923000000',
            'provincia' => 'Luanda',
            'endereco' => 'A definir',
            'descricao' => 'Instituição educativa',
        ]);

        $user = User::create([
            'nome' => $data['nome'],
            'email' => $data['email'],
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
