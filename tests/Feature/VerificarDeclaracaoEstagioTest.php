<?php

use App\Models\Central\DeclaracaoEstagio;
use App\Models\Central\HistoricoDeclaracaoEstagio;
use App\Models\Central\Tenant;
use App\Models\Central\User;
use App\Services\Central\DeclaracaoEstagioPdfService;
use App\Services\Central\DeclaracaoEstagioService;
use BaconQrCode\Common\ErrorCorrectionLevel as BaconErrorCorrectionLevel;
use BaconQrCode\Encoder\Encoder as BaconEncoder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config(['database.connections.central' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge('central');

    Schema::connection('central')->create('verificacoes_declaracoes_estagio', function ($table): void {
        $table->id();
        $table->char('token_hash', 64);
        $table->string('ip_origem', 45)->nullable();
        $table->boolean('valida');
        $table->timestamp('verificado_em');
        $table->timestamps();
    });
});

it('removes variable daily hours from the declaration schema', function (): void {
    expect(Schema::hasColumn('declaracoes_estagio', 'carga_horaria_diaria'))->toBeFalse();
});

it('shows only the verification details for a valid public QR token', function (): void {
    /** @var TestCase $this */
    $this->travelTo(now()->setDate(2026, 11, 25)->setTime(12, 13));
    $payload = json_encode([
        'aluno_nome' => 'Alexandra António Buco',
        'instituicao_nome' => 'Colégio Tunganetu',
        'nota' => '16.5',
        'avaliacao' => 'Muito bom',
        'empresa_nome' => 'Tunganetu',
        'emitido_em' => '2026-11-23 14:00:00',
    ], JSON_UNESCAPED_UNICODE);
    $token = rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.17'])
        ->get(route('central.verificar-estagio', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('central/declaracoes-estagio/verificar')
            ->where('verificadoEm', '25/11/2026 às 12:13')
            ->where('empresa.nome', 'Tunganetu')
            ->where('empresa.nif', '5003407764')
            ->where('empresa.telefones.0.link', '+244923678529')
            ->where('empresa.email', 'tunganetu@gmail.com')
            ->where('declaracao.aluno_nome', 'Alexandra António Buco')
            ->where('declaracao.instituicao_nome', 'Colégio Tunganetu')
            ->where('declaracao.nota', '16.5')
            ->where('declaracao.avaliacao', 'Muito bom')
            ->where('declaracao.duracao', '3 (três) meses')
            ->where('declaracao.carga_horaria', '120 horas')
            ->missing('declaracao.instituicao_tipo'));

    $this->assertDatabaseHas('verificacoes_declaracoes_estagio', [
        'token_hash' => hash('sha256', $token),
        'ip_origem' => '203.0.113.17',
        'valida' => true,
        'verificado_em' => '2026-11-25 12:13:00',
    ], 'central');
});

it('preserves central authentication when serving a public QR verification page', function (): void {
    /** @var TestCase $this */
    $superAdmin = User::factory()->create(['email_verified_at' => now()]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $payload = json_encode([
        'aluno_nome' => 'Alexandra António Buco',
        'instituicao_nome' => 'Colégio Tunganetu',
        'nota' => '16.5',
        'avaliacao' => 'Muito bom',
        'empresa_nome' => 'Tunganetu',
        'emitido_em' => '2026-11-23 14:00:00',
    ], JSON_UNESCAPED_UNICODE);
    $token = rtrim(strtr(base64_encode(Crypt::encryptString($payload)), '+/', '-_'), '=');

    $this->actingAs($superAdmin, 'web')
        ->get(route('central.verificar-estagio', ['token' => $token]))
        ->assertOk()
        ->assertHeader('Referrer-Policy', 'no-referrer')
        ->assertHeader('Cache-Control', 'no-store, private');

    $this->assertAuthenticatedAs($superAdmin, 'web');
});

it('records invalid verification attempts without storing the QR token in plain text', function (): void {
    /** @var TestCase $this */
    $token = 'invalid-public-token';

    $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.18'])
        ->get(route('central.verificar-estagio', ['token' => $token]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('declaracao', null)
            ->where('empresa.nome', 'Tunganetu'));

    $this->assertDatabaseHas('verificacoes_declaracoes_estagio', [
        'token_hash' => hash('sha256', $token),
        'ip_origem' => '203.0.113.18',
        'valida' => false,
    ], 'central');
});

it('purges verification records older than thirty days', function (): void {
    /** @var TestCase $this */
    $this->travelTo(now()->setDate(2026, 12, 26)->setTime(12, 0));

    DB::connection('central')->table('verificacoes_declaracoes_estagio')->insert([
        [
            'token_hash' => str_repeat('a', 64),
            'ip_origem' => '203.0.113.17',
            'valida' => true,
            'verificado_em' => '2026-11-25 11:59:59',
            'created_at' => '2026-11-25 11:59:59',
            'updated_at' => '2026-11-25 11:59:59',
        ],
        [
            'token_hash' => str_repeat('b', 64),
            'ip_origem' => '203.0.113.18',
            'valida' => false,
            'verificado_em' => '2026-11-26 12:00:00',
            'created_at' => '2026-11-26 12:00:00',
            'updated_at' => '2026-11-26 12:00:00',
        ],
    ]);

    Artisan::call('app:purge-old-stage-declaration-verifications');

    expect(DB::connection('central')->table('verificacoes_declaracoes_estagio')->pluck('token_hash')->all())
        ->toBe([str_repeat('b', 64)]);
});

it('verifies compact QR tokens and produces a lower-density matrix than legacy tokens', function (): void {
    /** @var TestCase $this */
    $compactPayload = [
        'Alexandra António Buco',
        'Colégio Tunganetu',
        '16.5',
        'Muito bom',
        '2026-11-23',
    ];
    $compactToken = rtrim(strtr(
        Crypt::encryptString(json_encode($compactPayload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)),
        '+/',
        '-_',
    ), '=');

    $legacyPayload = json_encode([
        'aluno_nome' => $compactPayload[0],
        'instituicao_nome' => $compactPayload[1],
        'nota' => $compactPayload[2],
        'avaliacao' => $compactPayload[3],
        'empresa_nome' => 'Tunganetu',
        'emitido_em' => '2026-11-23 00:00:00',
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $legacyToken = rtrim(strtr(
        base64_encode(Crypt::encryptString($legacyPayload)),
        '+/',
        '-_',
    ), '=');

    $compactQrWidth = BaconEncoder::encode(
        route('central.verificar-estagio', ['token' => $compactToken]),
        BaconErrorCorrectionLevel::L(),
        'UTF-8',
    )->getMatrix()->getWidth();
    $legacyQrWidth = BaconEncoder::encode(
        route('central.verificar-estagio', ['token' => $legacyToken]),
        BaconErrorCorrectionLevel::L(),
        'UTF-8',
    )->getMatrix()->getWidth();

    expect($compactQrWidth)->toBeLessThan($legacyQrWidth);

    $this->get(route('central.verificar-estagio', ['token' => $compactToken]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('declaracao.aluno_nome', 'Alexandra António Buco')
            ->where('declaracao.instituicao_nome', 'Colégio Tunganetu')
            ->where('declaracao.nota', '16.5')
            ->where('declaracao.avaliacao', 'Muito bom')
            ->where('declaracao.empresa_nome', 'Tunganetu')
            ->where('declaracao.emitido_em', '23/11/2026'));
});

it('rejects a final grade above twenty when issuing a declaration', function (): void {
    /** @var TestCase $this */
    $superAdmin = User::factory()->create(['email_verified_at' => now()]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $response = $this->actingAs($superAdmin, 'web')
        ->postJson(route('central.dashboard.declaracoes-estagio.store'), [
            'tenant_id' => 'tenant-inexistente',
            'aluno_id' => '10000000-0000-4000-8000-000000000003',
            'nota' => 20.1,
        ]);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors('nota');
});

it('requires the grade and responsibility details when issuing a declaration', function (): void {
    /** @var TestCase $this */
    $superAdmin = User::factory()->create(['email_verified_at' => now()]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $this->actingAs($superAdmin, 'web')
        ->postJson(route('central.dashboard.declaracoes-estagio.store'), [
            'tenant_id' => 'tenant-inexistente',
            'aluno_id' => '10000000-0000-4000-8000-000000000003',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'nota',
            'responsavel_nome',
            'responsavel_cargo',
        ]);
});

it('normalizes responsibility details and accepts half-point grades when issuing a declaration', function (): void {
    /** @var TestCase $this */
    config(['database.connections.central' => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge('central');

    Schema::connection('central')->create('tenants', function ($table): void {
        $table->string('id')->primary();
        $table->string('status')->nullable();
        $table->string('instituicao_nome')->nullable();
        $table->string('instituicao_tipo')->nullable();
        $table->json('data')->nullable();
        $table->timestamps();
    });

    $tenant = Tenant::create([
        'id' => 'colegio-de-teste',
        'status' => 'active',
        'instituicao_tipo' => 'colegio',
        'instituicao_nome' => 'Colégio de Teste',
    ]);
    DB::table('tenants')->insert([
        'id' => $tenant->getKey(),
        'data' => '{}',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $superAdmin = User::factory()->create(['email_verified_at' => now()]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $declaracao = new DeclaracaoEstagio([
        'aluno_nome' => 'Alexandra António Buco',
        'matricula' => '2026-001',
    ]);

    $servico = $this->mock(DeclaracaoEstagioService::class);
    $servico->shouldReceive('obterInstituicaoElegivel')
        ->once()
        ->with($tenant->getKey())
        ->andReturn($tenant);
    $servico->shouldReceive('emitir')
        ->once()
        ->withArgs(fn (Tenant $emissaoTenant, string $alunoId, string $utilizadorId, array $dados): bool => $emissaoTenant->getKey() === $tenant->getKey()
            && $alunoId === '10000000-0000-4000-8000-000000000003'
            && $utilizadorId === (string) $superAdmin->getKey()
            && $dados['nota'] === 16.5
            && ! array_key_exists('carga_horaria_diaria', $dados)
            && $dados['responsavel_nome'] === "José António D'Ávila"
            && $dados['responsavel_cargo'] === 'Director Pedagógico'
        )
        ->andReturn($declaracao);
    $servico->shouldReceive('registrarHistorico')
        ->once()
        ->with($declaracao)
        ->andReturn(new HistoricoDeclaracaoEstagio);

    $this->mock(DeclaracaoEstagioPdfService::class)
        ->shouldReceive('gerar')
        ->once()
        ->with($declaracao)
        ->andReturn('%PDF-1.4 teste');

    $this->actingAs($superAdmin, 'web')
        ->postJson(route('central.dashboard.declaracoes-estagio.store'), [
            'tenant_id' => $tenant->getKey(),
            'aluno_id' => '10000000-0000-4000-8000-000000000003',
            'nota' => 16.5,
            'carga_horaria_diaria' => 8,
            'responsavel_nome' => "  José   António D'Ávila  ",
            'responsavel_cargo' => '  Director   Pedagógico  ',
        ])
        ->assertOk()
        ->assertHeader('content-type', 'application/pdf');
});

it('rejects numbers in responsibility details', function (): void {
    /** @var TestCase $this */
    $superAdmin = User::factory()->create(['email_verified_at' => now()]);
    $superAdmin->assignRole(Role::create([
        'name' => 'SuperAdmin',
        'guard_name' => 'web',
    ]));

    $this->actingAs($superAdmin, 'web')
        ->postJson(route('central.dashboard.declaracoes-estagio.store'), [
            'tenant_id' => 'tenant-inexistente',
            'aluno_id' => '10000000-0000-4000-8000-000000000003',
            'nota' => 16,
            'responsavel_nome' => 'João 2',
            'responsavel_cargo' => 'Director 2',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'responsavel_nome',
            'responsavel_cargo',
        ])
        ->assertJsonPath(
            'errors.responsavel_nome.0',
            'O nome do responsável só pode conter letras, espaços, apóstrofo, ponto e hífen.',
        );
});
