<?php

use Aws\MockHandler;
use Aws\Result;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

uses(TestCase::class);

it('preserves scoped visibility and does not leak prefixes into the base S3 disk', function () {
    $requests = [];
    $handler = new MockHandler;
    $response = function ($command, $request) use (&$requests) {
        $requests[] = [
            'name' => $command->getName(),
            'key' => $command->toArray()['Key'] ?? null,
        ];

        if ($command->getName() === 'GetObjectAcl') {
            $key = $command->toArray()['Key'];

            return new Result([
                'Grants' => str_starts_with($key, 'public/')
                    ? [[
                        'Grantee' => [
                            'URI' => 'http://acs.amazonaws.com/groups/global/AllUsers',
                        ],
                        'Permission' => 'READ',
                    ]]
                    : [],
            ]);
        }

        return new Result;
    };

    for ($index = 0; $index < 10; $index++) {
        $handler->append($response);
    }

    Config::set('filesystems.disks.s3.handler', $handler);
    Storage::forgetDisk([config('filesystems.default'), 'private']);

    $publicDisk = null;
    $privateDisk = null;
    $baseDisk = null;
    $publicPath = 'scoped-public-visibility.txt';
    $privatePath = 'scoped-private-visibility.txt';
    $basePath = 'base-visibility.txt';

    try {
        $publicDisk = Storage::disk(config('filesystems.default'));
        $privateDisk = Storage::disk('private');
        $baseDisk = Storage::disk(config('filesystems.default'));

        expect($publicDisk->put($publicPath, 'public content'))->toBeTrue()
            ->and($publicDisk->getVisibility($publicPath))->toBe('public')
            ->and($privateDisk->put($privatePath, 'private content'))->toBeTrue()
            ->and($privateDisk->getVisibility($privatePath))->toBe('private')
            ->and($baseDisk->put($basePath, 'base content'))->toBeTrue();

        $putKeys = collect($requests)
            ->where('name', 'PutObject')
            ->pluck('key')
            ->values()
            ->all();

        expect($putKeys)->toContain('public/'.$publicPath)
            ->toContain('private/'.$privatePath)
            ->toContain($basePath)
            ->not->toContain('public/'.$basePath)
            ->not->toContain('private/'.$basePath);
    } finally {
        $publicDisk?->delete($publicPath);
        $privateDisk?->delete($privatePath);
        $baseDisk?->delete($basePath);
        Storage::forgetDisk([config('filesystems.default'), 'private']);
    }
});
