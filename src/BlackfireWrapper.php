<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

/**
 * A stream wrapper for sending Probe's output directly to Blackfire's API
 */
class BlackfireWrapper extends php_user_filter
{
    public $context;

    private $host = 'blackfire.io';
    private $path = '/agent-api/v1/profile-slots/%s';
    private $socket;
    private $size = 0;

    public function stream_open($path, $mode)
    {
        if ('wb' !== $mode) {
            return false;
        }

        $path = explode('://', $path, 2);
        $path = parse_url($path[1]);

        if (isset($path['scheme'], $path['host'])) {
            if ('https' === $path['scheme']) {
                $transport = 'ssl';
                $port = 443;
            } elseif ('http' === $path['scheme']) {
                $transport = 'tcp';
                $port = 80;
            } elseif ('php' === $path['scheme']) {
                $transport = 'php';
                $port = null;
            } else {
                return false;
            }
            $this->host = $path['host'];
            $url = $transport.'://'.$this->host;
            if (null !== $port) {
                if (empty($path['port'])) {
                    $url .= ':'.$port;
                } else {
                    if ($port != $path['port']) {
                        $this->host .= ':'.$path['port'];
                    }
                    $url .= ':'.$path['port'];
                }
            }
            if (!empty($path['path'])) {
                $this->path = $path['path'];
            }
        } else {
            $url = "ssl://{$this->host}:443";
        }

        if (!empty($path['query'])) {
            $this->path .= '?'.$path['query'];
        }

        if (0 !== strpos($url, 'php://')) {
            $context = PHP_VERSION_ID < 50600 ? array('ssl' => array('SNI_server_name' => $this->host)) : array();
            $this->socket = stream_socket_client($url, $errno, $errmsg, 15, STREAM_CLIENT_CONNECT, stream_context_create($context));
            stream_set_timeout($this->socket, 5);
        } else {
            $errmsg = 'Failed to open '.$url;
            $this->socket = fopen($url, 'wb');
        }

        if (!$this->socket) {
            user_error($errmsg);

            return false;
        }

        if (!in_array('blackfire.chunk', stream_get_filters())) {
            stream_filter_register('blackfire.chunk', __CLASS__);
        }

        return true;
    }

    public function stream_write($data)
    {
        if ($this->params) {
            hash_update($this->params['crc32b'], $data);
            $this->size += strlen($data);
            if (!$w = fwrite($this->socket, $data)) {
                return $w;
            }
        } else {
            $this->params = array(
                'boundary' => $boundary = md5(mt_rand()*mt_rand()).md5(mt_rand()*mt_rand()),
                'deflate' => $deflate = in_array('zlib.*', stream_get_filters()),
                'crc32b' => $this->crc32 = hash_init('crc32b'),
                'size' => &$this->size,
            );

            $serverId = '';
            $slotId = '';
            $authToken = '';
            $post = array();

            foreach (explode("\n", $data) as $field) {
                $field = explode(":", $field, 2);
                switch ($field[0]) {
                    case 'Blackfire-Query':
                        $authToken = trim($field[1]);
                        parse_str($authToken, $field);
                        foreach ($field as $k => $v) {
                            if ('agentIds' === $k) {
                                $v = explode(',', $v);
                                $authToken = $v[0].':'.$authToken;
                                foreach ($v as $v) {
                                    $post[] = array('agents[]', $v);
                                }
                            } else {
                                if ('profileSlot' === $k) {
                                    $slotId = $v;
                                }
                                $post[] = array($k, $v);
                            }
                        }
                        $authToken = base64_encode($authToken);
                        break;
                    case 'Blackfire-Auth':
                        $serverId = trim($field[1]);
                        $serverId = "X-{$field[0]}: {$field[1]}\r\n";
                        break;
                }
            }

            $http = sprintf("POST {$this->path} HTTP/1.1\r\n", $slotId)
                ."Host: {$this->host}\r\n"
                ."User-Agent: Blackfire-php-sdk\r\n"
                ."Authorization: Basic {$authToken}\r\n"
                ."Content-Type: multipart/form-data; boundary={$boundary}\r\n"
                ."Transfer-Encoding: chunked\r\n"
                .$serverId
                ."\r\n";

            if (!$w = fwrite($this->socket, $http)) {
                return $w;
            }
            stream_filter_append($this->socket, 'blackfire.chunk', STREAM_FILTER_WRITE, $this->params);
            $http = '';

            foreach ($post as $field) {
                $http .= '--'.$boundary."\r\n"
                    .'Content-Disposition: form-data; name="'.$field[0].'"'."\r\n"
                    ."\r\n"
                    .$field[1]."\r\n";
            }

            $http .= '--'.$boundary."\r\n"
                ."Content-Disposition: form-data; name=\"payload\"; filename=\"graph.dat\"\r\n"
                ."Content-Type: application/octet-stream\r\n"
                ."\r\n";

            if ($deflate) {
                $http .= "\x1F\x8B\x08\x08".pack("V", time())."\0\xFFgraph.dat\0";
            }
            if (!$w = fwrite($this->socket, $http)) {
                return $w;
            }
            if ($deflate) {
                stream_filter_prepend($this->socket, 'zlib.deflate', STREAM_FILTER_WRITE);
            }
        }

        return strlen($data);
    }

    public function stream_flush()
    {
        return fflush($this->socket);
    }

    public function stream_close()
    {
        fclose($this->socket);
        $this->socket = $this->params = null;
        $this->size = 0;
    }

    public function stream_lock()
    {
        return false;
    }

    public function filter($in, $out, &$consumed, $closing)
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            $bucket->data = dechex($bucket->datalen)."\r\n{$bucket->data}\r\n";
            stream_bucket_append($out, $bucket);
        }

        if ($closing) {
            $data = '';
            if ($this->params['deflate']) {
                $crc = hash_final($this->params['crc32b'], true);
                $data = $crc[3].$crc[2].$crc[1].$crc[0].pack("V", $this->params['size']);
            }
            $data .= "\r\n--{$this->params['boundary']}--\r\n";
            $data = dechex(strlen($data))."\r\n{$data}\r\n0\r\n\r\n";
            stream_bucket_append($out, stream_bucket_new($this->stream, $data));
        }

        return PSFS_PASS_ON;
    }
}
