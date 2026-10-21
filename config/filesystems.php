<?php

return [

    'default' => env('FILESYSTEM_DISK', 'local'),

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => env('LOCAL_DISK_ROOT', storage_path('app/private')),
            'serve' => true,
            'throw' => false,
        ],

        /*
        |----------------------------------------------------------------------
        | Disk específico para o tenant CS (RAM)
        |----------------------------------------------------------------------
        |
        | Usado para forçar escritas em /tmp quando o disco mau falha no
        | symlink. O PlanificacaoStorageService tenta este disk primeiro.
        |
        */
        'tenant-storage' => [
            'driver' => 'local',
            'root' => env('TENANT_STORAGE_ROOT', '/tmp/sge/tenant-cs-app/app'),
      
            'throw' => true,
        ],

        'private' => env('AWS_BUCKET') ? [
            'driver' => 'scoped',
            'disk' => 's3',
            'prefix' => 'private',
            'throw' => true,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'visibility' => 'private',
            'throw' => true,
        ],

        'local_public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => true,
        ],

        'public' => env('AWS_BUCKET') ? [
            'driver' => 'scoped',
            'disk' => 's3',
            'prefix' => 'public',
            'throw' => true,
        ] : [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw' => false,
        ],

        's3' => [
            'driver' => 's3',
            'region' => env('AWS_DEFAULT_REGION', 'eu-north-1'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'throw' => true,
            'use_path_style_endpoint' => false,
            'visibility' => 'private',
        ],

    ],

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];