#!/usr/bin/env php
<?php

// Use this proxy to inspect the traffic between profiled servers and
// blackfire's servers.
// Set the `collector` setting of the agent to `http://127.0.0.1:8383`
// Set the `endpoint` setting of the CLI tool to `http://127.0.0.1:8383`
// Then start this script in a dedicated console and look at its standard output.

$backendEndpoint = isset($argv[1]) ? $argv[1] : 'https://blackfire.io';
$upstreamHost = isset($argv[2]) ? $argv[2] : '127.0.0.1:8383';

error_reporting(-1);
$IN = "\033[42m<-\033[0m ";
$OUT = "\033[41m->\033[0m ";

$log = fopen('php://stdout', 'wb');
$proxy = stream_socket_server('tcp://'.$upstreamHost, $errno, $errstr);

$backendInfo = parse_url($backendEndpoint);
if (false === $backendInfo) {
    fwrite($log, "The backendEndpoint is not valid ($backendEndpoint)\n");
    exit(1);
}
if ('https' === $backendInfo['scheme']) {
    $backendSocketUrl = sprintf('ssl://%s:%s', $backendInfo['host'], isset($backendInfo['port']) ? $backendInfo['port'] : 443);
} else {
    $backendSocketUrl = sprintf('%s:%s', $backendInfo['host'], isset($backendInfo['port']) ? $backendInfo['port'] : 80);
}

$rewrite = array(
    'HTTP/1.1' => 'HTTP/1.0',
    $upstreamHost => $backendInfo['host'],
);

fwrite($log, "Listening on $upstreamHost\n");
fwrite($log, "Forwarding to $backendSocketUrl\n");

while ($upstream = stream_socket_accept($proxy, -1)) {
    $backend = @stream_socket_client($backendSocketUrl, $errno, $errstr);
    if (false === $backend) {
        fwrite($log, "Could not connect to the backend ($errstr)\n");
        exit(1);
    }

    stream_set_timeout($upstream, 1);
    stream_set_timeout($backend, 1);

    foreach ($rewrite as $k => $v) {
        $line = str_replace($k, $v, fgets($upstream));
        fwrite($log, $OUT.$line);
        fwrite($backend, $line, strlen($line));
    }

    $write = array();

    while (true) {
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
                        fwrite($log, ($upstream === $socket ? $OUT : $IN).rtrim($zip, "\r\n").PHP_EOL);
                    }
                } else {
                    fwrite($log, ($upstream === $socket ? $OUT : $IN).'COMPRESSED DATA ('.strlen($zip).' bytes). Enable zlib extension to inflate.'.PHP_EOL);
                }
            } elseif (PHP_VERSION_ID >= 50400 && is_array($json = @json_decode($line, true))) {
                foreach (explode("\n", json_encode($json, JSON_PRETTY_PRINT)) as $json) {
                    fwrite($log, ($upstream === $socket ? $OUT : $IN).rtrim($json, "\r").PHP_EOL);
                }
            } else {
                fwrite($log, ($upstream === $socket ? $OUT : $IN).rtrim($line, "\r\n").PHP_EOL);
            }

            fwrite($upstream === $socket ? $backend : $upstream, $line, strlen($line));
        }
    }

    @fclose($backend);
    @fclose($upstream);
}
