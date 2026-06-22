<?php

namespace App\Helpers;

class BrowsershotHelper
{
    public static function getChromePath(): string
    {
        // Se tiver no .env, usa direto
        if (env('CHROME_PATH') && file_exists(env('CHROME_PATH'))) {
            return env('CHROME_PATH');
        }

        // Detecta automaticamente no sistema
        $candidates = [
            shell_exec('which google-chrome'),
            shell_exec('which google-chrome-stable'),
            shell_exec('which chromium-browser'),
            shell_exec('which chromium'),
            '/usr/bin/google-chrome',
            '/usr/bin/chromium-browser',
            '/snap/bin/chromium',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Chrome não encontrado no sistema.');
    }

    public static function getNodeBinary(): string
    {
        if (env('NODE_BINARY') && file_exists(env('NODE_BINARY'))) {
            return env('NODE_BINARY');
        }

        $candidates = [
            shell_exec('which node'),
            shell_exec('which nodejs'),
            '/usr/bin/node',
            '/usr/local/bin/node',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('Node.js não encontrado no sistema.');
    }

    public static function getNpmBinary(): string
    {
        if (env('NPM_BINARY') && file_exists(env('NPM_BINARY'))) {
            return env('NPM_BINARY');
        }

        $candidates = [
            shell_exec('which npm'),
            '/usr/bin/npm',
            '/usr/local/bin/npm',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('NPM não encontrado no sistema.');
    }
}