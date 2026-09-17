<?php

test('production 404 responses use the custom Inertia error page', function () {
    app()->detectEnvironment(fn (): string => 'production');

    $response = $this->withHeaders([
        'X-Inertia' => 'true',
        'X-Requested-With' => 'XMLHttpRequest',
        'Accept' => 'text/html, application/xhtml+xml',
    ])->get('/missing-page-for-error-test');

    $response->assertNotFound();
    $response->assertJsonPath('component', 'errors/error-page');
    $response->assertJsonPath('props.status', 404);
});
