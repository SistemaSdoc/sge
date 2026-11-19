<?php

use App\Services\Tenant\QuadroHonra\AvaliarMediaQuadroHonra;

it('rejects a student whose exact average is below the minimum even when the input grades are integers', function (): void {
    $avaliarMedia = new AvaliarMediaQuadroHonra;

    expect($avaliarMedia->calcular([13.0, 14.0, 14.0], 14.0))->toBeNull();
});

it('compares the exact average with the minimum and returns it without rounding', function (): void {
    $avaliarMedia = new AvaliarMediaQuadroHonra;

    expect($avaliarMedia->calcular([14.0, 14.0, 13.999], 14.0))->toBeNull();

    $media = $avaliarMedia->calcular([14.0, 14.0, 14.2], 14.0);

    expect($media)->toBeGreaterThan(14.0);
    expect($media)->toBeLessThan(14.1);
});

it('does not calculate an average when there are no discipline grades', function (): void {
    $avaliarMedia = new AvaliarMediaQuadroHonra;

    expect($avaliarMedia->calcular([], 14.0))->toBeNull();
});
