<?php

use App\Notifications\Concerns\TenantNotificationUrl;
use App\Services\Tenant\TenantUrlGenerator;
use Tests\TestCase;

uses(TestCase::class);

it('builds a tenant route on the explicitly supplied recipient domain', function () {
    $notification = new class
    {
        use TenantNotificationUrl;

        public function buildUrl(string $routeName, ?string $domain = null): string
        {
            return $this->tenantNotificationUrl($routeName, [], $domain);
        }
    };

    $applicationUrl = config('app.url');

    try {
        config(['app.url' => 'https://central.example.test']);
        $url = $notification->buildUrl('tenant.dashboard', 'recipient-portal.test');

        expect(parse_url($url, PHP_URL_SCHEME))->toBe('https')
            ->and(parse_url($url, PHP_URL_HOST))->toBe('recipient-portal.test')
            ->and(parse_url($url, PHP_URL_PATH))->toBe('/dashboard');
    } finally {
        config(['app.url' => $applicationUrl]);
    }
});

it('does not fall back to the central host when tenant domain is missing', function () {
    $notification = new class
    {
        use TenantNotificationUrl;

        public function buildUrl(): string
        {
            return $this->tenantNotificationUrl('tenant.dashboard');
        }
    };

    expect(fn () => $notification->buildUrl())
        ->toThrow(LogicException::class, 'without a tenant domain');
});

it('generates the registered notification CTA destinations on the recipient portal', function () {
    $applicationUrl = config('app.url');
    $domain = 'recipient-portal.example.test';
    $papParameters = [
        'instituicao' => 'institution-test',
        'cursoTutelado' => 'course-test',
        'cursoClasse' => 'class-test',
        'cursoClasseTurno' => 'shift-test',
        'turma' => 'classroom-test',
        'grupoPap' => 'group-test',
    ];

    $routes = [
        [
            'tenant.dashboard.instituicoes.cursos-tutelados.classes.turnos.turmas.pap.show',
            $papParameters,
            'https://recipient-portal.example.test/dashboard/instituicoes/institution-test/cursos-tutelados/course-test/classes/class-test/turnos/shift-test/turmas/classroom-test/pap/group-test',
        ],
        [
            'tenant.dashboard.colegios.cursos.classes.turnos.turmas.pap.show',
            [
                'colegio' => 'school-test',
                'cursoTutelado' => 'course-test',
                'cursoClasse' => 'class-test',
                'cursoClasseTurno' => 'shift-test',
                'turma' => 'classroom-test',
                'grupoPap' => 'group-test',
            ],
            'https://recipient-portal.example.test/dashboard/colegios/school-test/cursos/course-test/classes/class-test/turnos/shift-test/turmas/classroom-test/pap/group-test',
        ],
        [
            'tenant.dashboard.instituicoes.cursos-tutelados.show',
            ['instituicao' => 'institution-test', 'cursoTutelado' => 'course-test'],
            'https://recipient-portal.example.test/dashboard/instituicoes/institution-test/cursos-tutelados/course-test',
        ],
        [
            'tenant.dashboard.planificacoes.show',
            ['planificacao' => 'planification-test'],
            'https://recipient-portal.example.test/dashboard/planificacoes/planification-test',
        ],
        [
            'tenant.dashboard.professor.provas.index',
            [],
            'https://recipient-portal.example.test/dashboard/professor/provas',
        ],
        [
            'tenant.dashboard',
            [],
            'https://recipient-portal.example.test/dashboard',
        ],
        [
            'tenant.login',
            [],
            'https://recipient-portal.example.test',
        ],
        [
            'tenant.dashboard.profile.edit',
            [],
            'https://recipient-portal.example.test/dashboard/settings/profile',
        ],
        [
            'tenant.dashboard.pautas.solicitacoes.index',
            [],
            'https://recipient-portal.example.test/dashboard/pautas/solicitacoes',
        ],
        [
            'tenant.dashboard.pautas.index',
            [],
            'https://recipient-portal.example.test/dashboard/pautas',
        ],
        [
            'password.reset',
            ['token' => 'reset-token', 'email' => 'user@example.test'],
            'https://recipient-portal.example.test/reset-password/reset-token?email=user%40example.test',
        ],
    ];

    try {
        config(['app.url' => 'https://central.example.test']);

        foreach ($routes as [$routeName, $parameters, $expectedUrl]) {
            expect(app(TenantUrlGenerator::class)->route($routeName, $parameters, $domain))
                ->toBe($expectedUrl);
        }
    } finally {
        config(['app.url' => $applicationUrl]);
    }
});
