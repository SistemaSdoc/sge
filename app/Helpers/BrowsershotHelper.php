<?php

namespace App\Helpers;

class BrowsershotHelper
{
    public static function getChromePath(): string
    {
        $configuredPath = (string) config('browsershot.chrome_path', '');

        if ($configuredPath && file_exists($configuredPath)) {
            return $configuredPath;
        }

        $candidates = [
            shell_exec('which google-chrome'),
            shell_exec('which google-chrome-stable'),
            shell_exec('which chromium-browser'),
            shell_exec('which chromium'),
            '/usr/bin/google-chrome',
            '/usr/bin/google-chrome-stable',
            '/usr/bin/chromium-browser',
            '/usr/bin/chromium',
            '/snap/bin/chromium',
            '/snap/bin/google-chrome',
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
        $configuredPath = (string) config('browsershot.node_binary', '');

        if ($configuredPath && file_exists($configuredPath)) {
            return $configuredPath;
        }

        $candidates = [
            shell_exec('which node'),
            shell_exec('which nodejs'),
            // nvm — qualquer utilizador
            ...self::nvmCandidates('node'),
            '/usr/bin/node',
            '/usr/local/bin/node',
            '/usr/local/node/bin/node',
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
        $configuredPath = (string) config('browsershot.npm_binary', '');

        if ($configuredPath && file_exists($configuredPath)) {
            return $configuredPath;
        }

        $candidates = [
            shell_exec('which npm'),
            // nvm — qualquer utilizador
            ...self::nvmCandidates('npm'),
            '/usr/bin/npm',
            '/usr/local/bin/npm',
            '/usr/local/node/bin/npm',
        ];

        foreach ($candidates as $path) {
            $path = trim((string) $path);
            if ($path && file_exists($path)) {
                return $path;
            }
        }

        throw new \RuntimeException('NPM não encontrado no sistema.');
    }

    // Procura o binário em todas as versões nvm de todos os utilizadores
    private static function nvmCandidates(string $binary): array
    {
        $candidates = [];

        // Pastas home de todos os utilizadores
        $homes = glob('/home/*', GLOB_ONLYDIR) ?: [];
        $homes[] = '/root';

        foreach ($homes as $home) {
            $nvmDir = $home.'/.nvm/versions/node';
            if (! is_dir($nvmDir)) {
                continue;
            }

            // Pega todas as versões instaladas e ordena da mais recente
            $versions = glob($nvmDir.'/v*', GLOB_ONLYDIR) ?: [];
            rsort($versions); // v24 antes de v20

            foreach ($versions as $version) {
                $path = $version.'/bin/'.$binary;
                if (file_exists($path)) {
                    $candidates[] = $path;
                }
            }
        }

        return $candidates;
    }
}
