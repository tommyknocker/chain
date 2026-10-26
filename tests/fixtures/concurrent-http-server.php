<?php

declare(strict_types=1);

$address = $argv[1] ?? '';
$server = stream_socket_server('tcp://' . $address, $errorCode, $errorMessage);
if ($server === false) {
    fwrite(STDERR, $errorMessage);
    exit(1);
}
stream_set_blocking($server, false);

/** @var array<int, array{stream: resource, request: string, dueAt: float|null}> $clients */
$clients = [];

while (true) {
    $read = [$server];
    foreach ($clients as $client) {
        if ($client['dueAt'] === null) {
            $read[] = $client['stream'];
        }
    }
    $write = null;
    $except = null;
    stream_select($read, $write, $except, 0, 10_000);

    foreach ($read as $stream) {
        if ($stream === $server) {
            while (($client = @stream_socket_accept($server, 0)) !== false) {
                stream_set_blocking($client, false);
                $clients[(int) $client] = ['stream' => $client, 'request' => '', 'dueAt' => null];
            }

            continue;
        }

        $id = (int) $stream;
        $chunk = fread($stream, 8192);
        if ($chunk === '' && feof($stream)) {
            fclose($stream);
            unset($clients[$id]);

            continue;
        }
        $clients[$id]['request'] .= $chunk;
        if (str_contains($clients[$id]['request'], "\r\n\r\n")) {
            $clients[$id]['dueAt'] = microtime(true) + 0.25;
        }
    }

    foreach ($clients as $id => $client) {
        if ($client['dueAt'] !== null && $client['dueAt'] <= microtime(true)) {
            fwrite($client['stream'], "HTTP/1.1 200 OK\r\nContent-Type: text/plain\r\nContent-Length: 4\r\nConnection: close\r\n\r\nslow");
            fclose($client['stream']);
            unset($clients[$id]);
        }
    }
}
