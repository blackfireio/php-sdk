#!/usr/bin/env php
<?php

// Use this proxy to inspect the traffic between profiled servers and
// blackfire's servers.
// Set the `collector` setting of the agent to `http://127.0.0.1:8383`
// Set the `endpoint` setting of the CLI tool to `http://127.0.0.1:8383`
// Then start this script in a dedicated console and look at its standard output.

$upstreamHost = '127.0.0.1:8383';
$backendHost = 'blackfire.io';

error_reporting(-1);
$IN = "\033[42m<-\033[0m ";
$OUT = "\033[41m->\033[0m ";
$rewrite = array(
    'HTTP/1.1' => 'HTTP/1.0',
    $upstreamHost => $backendHost,
);

$log = fopen('php://stdout', 'wb');
$proxy = stream_socket_server('tcp://'.$upstreamHost, $errno, $errstr);
fwrite($log, "Listening on http://{$upstreamHost}\n");
fwrite($log, "Forwarding to https://{$backendHost}\n");

while ($upstream = stream_socket_accept($proxy, -1)) {
    $backend = stream_socket_client("ssl://{$backendHost}:443", $errno, $errstr);

    stream_set_timeout($upstream, 1);
    stream_set_timeout($backend, 1);

    foreach ($rewrite as $k => $v) {
        $line = str_replace($k, $v, fgets($upstream));
        fwrite($log, $OUT.$line);
        fwrite($backend, $line, strlen($line));
    }

    $write = array();

    for (;;) {
        $read = array($upstream, $backend);
        if (!stream_select($read, $write, $write, 5)) {
            break;
        }
        foreach ($read as $socket) {
            if (false === $line = fgets($socket)) {
                break 2;
            }
            if ("\x1F" === $line[0] && "\x8B" === $line[1]) {
                for ($zip = $line;;) {
                    fwrite($upstream === $socket ? $backend : $upstream, $line, strlen($line));
                    $line = fgets($socket);
                    if ('-' === $line[0] && '-' === $line[0] && "--\r\n" === substr($line, -4)) {
                        break;
                    }
                    $zip .= $line;
                }
                if (extension_loaded('zlib')) {
                    if (function_exists('zlib_decode')) {
                        $zip = zlib_decode($zip);
                    } else {
                        $zip = file_get_contents('compress.zlib://data:application/octet-stream;base64,'.base64_encode($zip));
                    }

                    foreach (explode("\n", $zip) as $zip) {
                        fwrite($log, ($upstream === $socket ? $OUT : $IN).rtrim($zip, "\n")."\n");
                    }
                } else {
                    fwrite($log, ($upstream === $socket ? $OUT : $IN).'COMPRESSED DATA ('.strlen($zip)." bytes). Enable zlib extension to inflate.\n");
                }
            }

            fwrite($log, ($upstream === $socket ? $OUT : $IN).rtrim($line, "\n")."\n");
            fwrite($upstream === $socket ? $backend : $upstream, $line, strlen($line));
        }
    }

    @fclose($backend);
    @fclose($upstream);
}
