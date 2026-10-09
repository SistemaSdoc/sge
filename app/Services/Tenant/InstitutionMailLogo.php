<?php

namespace App\Services\Tenant;

use App\Models\Tenant\Instituicao;
use Illuminate\Support\Facades\Storage;
use Throwable;

class InstitutionMailLogo
{
    /**
     * @return array{contents: string, mime: string, name: string}|null
     */
    public function image(?Instituicao $instituicao): ?array
    {
        if (! $instituicao?->logo) {
            return null;
        }

        $extension = strtolower(pathinfo($instituicao->logo, PATHINFO_EXTENSION));
        $mime = match ($extension) {
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'webp' => 'image/webp',
            default => null,
        };

        if ($mime === null) {
            return null;
        }

        $disks = array_unique(['public', config('filesystems.default')]);

        foreach ($disks as $diskName) {
            try {
                $disk = Storage::disk($diskName);

                if ($disk->exists($instituicao->logo)) {
                    $contents = $disk->get($instituicao->logo);

                    if (! is_string($contents) || $contents === '') {
                        continue;
                    }

                    return [
                        'contents' => $contents,
                        'mime' => $mime,
                        'name' => basename($instituicao->logo),
                    ];
                }
            } catch (Throwable) {
                continue;
            }
        }

        return null;
    }
}
