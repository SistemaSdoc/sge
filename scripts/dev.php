<?php

function getLocalIp(): string
{
    $socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
    socket_connect($socket, '8.8.8.8', 80);
    socket_getsockname($socket, $ip);
    socket_close($socket);

    return $ip;
}

$useIp = in_array('--ip', $argv ?? []);

$host = $useIp ? getLocalIp() : '127.0.0.1';
$mode = $useIp ? "> IP da rede: {$host}" : "> Localhost: {$host}";

echo "{$mode}\n\n";

$cmd = implode(' ', [
    'npx concurrently',
    '-c "#93c5fd,#c4b5fd,#fb7185,#fdba74"',
    "\"php artisan serve --host={$host}\"",
    '"php artisan queue:listen --tries=1 --timeout=0"',
    '--names=server,queue,logs,vite',
    '--kill-others',
]);

$proc = proc_open($cmd, [STDIN, STDOUT, STDERR], $pipes);
if (is_resource($proc)) {
    proc_close($proc);
}
