<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) SensioLabs <contact@sensiolabs.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

if (!defined('UPROFILER_FLAGS_NO_BUILTINS')) {
    define('UPROFILER_FLAGS_NO_BUILTINS', defined('XHPROF_FLAGS_NO_BUILTINS') ? XHPROF_FLAGS_NO_BUILTINS : 1);
}

if (!defined('UPROFILER_FLAGS_CPU')) {
    define('UPROFILER_FLAGS_CPU', defined('XHPROF_FLAGS_CPU') ? XHPROF_FLAGS_CPU : 2);
}

if (!defined('UPROFILER_FLAGS_MEMORY')) {
    define('UPROFILER_FLAGS_MEMORY', defined('XHPROF_FLAGS_MEMORY') ? XHPROF_FLAGS_MEMORY : 4);
}

/**
 * This is a PHP 5.2 compatible fallback implementation of the Blackfire extension.
 * The interfaces and behavior are the same, or as close as possible.
 * It uses xhprof or uprofiler to gather profiling metrics and push them to Blackfire.
 * When the extension is loaded, this PHP fallback is not loaded.
 *
 * A general rule of design is that this fallback (as the extension) does not generate any exception
 * nor any PHP notice/warning/etc. Instead, a log facility is provided where all messages shall be written.
 */
class BlackfireProbe
{
    protected $options = array(
        'blackfire_yml' => false,
        'composer_lock' => false,
        'config' => true,
        'timespan' => false,
        'server_keys' => array(
            'HTTP_HOST',
            'HTTP_USER_AGENT',
            'HTTPS',
            'REQUEST_METHOD',
            'REQUEST_URI',
            'SERVER_ADDR',
            'SERVER_PORT',
            'SERVER_SOFTWARE',
            '_',
            'argv',
        ),
        'ignored_functions' => array(
            'array_map',
            'array_filter',
            'array_reduce',
            'array_walk',
            'array_walk_recursive',
            'call_user_func',
            'call_user_func_array',
            'call_user_method',
            'call_user_method_array',
            'forward_static_call',
            'forward_static_call_array',
            'iterator_apply',
        ),
    );

    private $seqId;
    private $fileFormat = 'BlackfireProbe';
    private $autoEnabled = false;
    private $stale = false;
    private $profiler;
    private $agentSocket;
    private $agentTimeout;
    private $outputStream;
    private $logLevel = 1;
    private $logFile = '';
    private $isEnabled = false;
    private $responseLine = '';
    private $challenge;
    private $profileTitle;
    private $configYml;
    private $signedArgs;
    private $signature;
    private $flags;
    private $configuration;
    private static $nextSeqId = 1;
    private static $probe;
    private static $profilerIsEnabled = false;
    private static $shutdownFunctionRegistered = false;
    private static $defaultAgentSocket = false;
    private static $urlEncMap = array(
        '%21' => '!', '%22' => '"', '%23' => '#', '%24' => '$', '%27' => "'",
        '%28' => '(', '%29' => ')', '%2A' => '*', '%2C' => ',', '%2F' => '/',
        '%3A' => ':', '%3B' => ';', '%3C' => '<', '%3D' => '=', '%3E' => '>',
        '%40' => '@', '%5B' => '[', '%5C' => '\\','%5D' => ']', '%5E' => '^',
        '%60' => '`', '%7B' => '{', '%7C' => '|', '%7D' => '}', '%7E' => '~',
    );

    /**
     * Returns a global singleton and enables it by default.
     *
     * Uses the X-Blackfire-Query HTTP header to create this singleton on its first use.
     *
     * Additionally, this function enables the probe, except when the just said string
     * contains an auto_enable=0 URL parameter.
     *
     * @return self
     *
     * @api
     */
    public static function getMainInstance()
    {
        if (null !== self::$probe) {
            return self::$probe;
        }

        if (isset($_SERVER['HTTP_X_BLACKFIRE_QUERY'])) {
            $query = $_SERVER['HTTP_X_BLACKFIRE_QUERY'];
        } elseif (isset($_SERVER['BLACKFIRE_QUERY'])) {
            $query = $_SERVER['BLACKFIRE_QUERY'];
        } else {
            $query = '';
        }

        self::$probe = new static($query);

        parse_str($query, $query);

        if (!isset($query['auto_enable']) || $query['auto_enable']) {
            if (self::$probe->isVerified()) {
                if (self::$probe->blackfireYmlAsked()) {
                    self::$probe->info('blackfire.yml asked.');

                    $config = self::$probe->getConfiguration();
                    if (null === $config) {
                        self::$probe->responseLine .= '&no-blackfire-yaml';
                    } else {
                        self::$probe->responseLine .= '&blackfire-yml-size='.strlen($config);
                    }

                    if (!headers_sent()) {
                        header('X-'.self::$probe->getResponseLine());
                    }

                    echo $config;

                    exit(0);
                }

                self::$probe->autoEnabled = self::$probe->enable();
            }
        }

        return self::$probe;
    }

    /**
     * Tells whether any probes are currently profiling or not.
     *
     * @return bool
     *
     * @api
     */
    public static function isEnabled()
    {
        return self::$profilerIsEnabled;
    }

    /**
     * Instantiate a probe object.
     *
     * @param string $query       An URL-encoded string that configures the probe. Part of the string is signed.
     * @param string $envId       An id that is given to the agent for signature impersonation.
     * @param string $envToken    The token associated to $envId.
     * @param string $agentSocket The URL where profiles will be written (directory, socket or TCP destination).
     *
     * @api
     */
    public function __construct($query, $envId = null, $envToken = null, $agentSocket = null)
    {
        if (false !== self::$defaultAgentSocket) {
        } elseif ('\\' === DIRECTORY_SEPARATOR) {
            self::$defaultAgentSocket = 'tcp://127.0.0.1:8307';
        } else {
            if ('Darwin' === PHP_OS) {
                $defaultAgentSocket = '/usr/local/var/run/blackfire-agent.sock';
            } else {
                $defaultAgentSocket = '/var/run/blackfire/agent.sock';
            }

            if (!file_exists($defaultAgentSocket) && (ini_get('uprofiler.output_dir') || ini_get('xhprof.output_dir'))) {
                self::$defaultAgentSocket = null;
            } else {
                self::$defaultAgentSocket = 'unix://'.$defaultAgentSocket;
            }
        }

        $this->seqId = self::$nextSeqId++;
        $query = preg_split('/(?:^|&)signature=(.+?)(?:&|$)/', $query, 2, PREG_SPLIT_DELIM_CAPTURE);
        list($this->challenge, $this->signature, $args) = $query + array(1 => '', '');
        $this->signature = rawurldecode($this->signature);
        parse_str($args, $args);
        parse_str($this->challenge, $this->signedArgs);
        $query = array(
            'BLACKFIRE_SERVER_ID' => get_cfg_var('blackfire.server_id') ?: null,
            'BLACKFIRE_SERVER_TOKEN' => get_cfg_var('blackfire.server_token') ?: null,
            'BLACKFIRE_ENV_ID' => get_cfg_var('blackfire.env_id') ?: null,
            'BLACKFIRE_ENV_TOKEN' => get_cfg_var('blackfire.env_token') ?: null,
            'BLACKFIRE_AGENT_SOCKET' => get_cfg_var('blackfire.agent_socket') ?: null,
            'BLACKFIRE_AGENT_TIMEOUT' => get_cfg_var('blackfire.agent_timeout') ?: null,
            'BLACKFIRE_LOG_LEVEL' => get_cfg_var('blackfire.log_level') ?: null,
            'BLACKFIRE_LOG_FILE' => get_cfg_var('blackfire.log_file') ?: null,
        );
        foreach ($query as $k => $v) {
            if (isset($_SERVER[$k])) {
                $query[$k] = $_SERVER[$k];
            }
        }

        $this->envId = $envId ?: $query['BLACKFIRE_ENV_ID'] ?: $query['BLACKFIRE_SERVER_ID'];
        $this->envToken = $envToken ?: $query['BLACKFIRE_ENV_TOKEN'] ?: $query['BLACKFIRE_SERVER_TOKEN'];
        $this->agentSocket = $agentSocket;
        $this->agentSocket or $this->agentSocket = $query['BLACKFIRE_AGENT_SOCKET'];
        $this->agentSocket or $this->agentSocket = self::$defaultAgentSocket;
        $this->agentSocket or $this->agentSocket = ini_get('uprofiler.output_dir');
        $this->agentSocket or $this->agentSocket = ini_get('xhprof.output_dir');
        $this->agentTimeout = 1000000 * $query['BLACKFIRE_AGENT_TIMEOUT'];
        $this->agentTimeout or $this->agentTimeout = 250000;
        $query['BLACKFIRE_LOG_LEVEL'] and $this->logLevel = $query['BLACKFIRE_LOG_LEVEL'];
        $query['BLACKFIRE_LOG_FILE'] and $this->logFile = $query['BLACKFIRE_LOG_FILE'];
        $this->aggregSamples = isset($args['aggreg_samples']) && is_string($args['aggreg_samples']) ? max((int) $args['aggreg_samples'], 1) : 1;
        isset($args['profile_title']) and $this->profileTitle = $args['profile_title'];
        isset($args['config_yml']) and $this->configYml = $args['config_yml'];

        if ($this->logFile && strpos($this->logFile, '://') === false) {
            $this->logFile = 'file://'.$this->logFile;
        } elseif (!$this->logFile) {
            $this->logFile = 'php://stderr';
        }

        empty($args['flag_cpu']) or $this->flags |= UPROFILER_FLAGS_CPU;
        empty($args['flag_memory']) or $this->flags |= UPROFILER_FLAGS_MEMORY;
        empty($args['flag_no_builtins']) or $this->flags |= UPROFILER_FLAGS_NO_BUILTINS;
        $this->options['blackfire_yml'] = !empty($args['flag_yml']);
        $this->options['composer_lock'] = !empty($args['flag_composer']);
        $this->options['timespan'] = !empty($args['flag_timespan']);

        if (function_exists('uprofiler_enable')) {
            $this->profiler = 'uprofiler';
        } elseif (function_exists('xhprof_enable')) {
            $this->profiler = 'xhprof';
        }

        if ($this->logLevel >= 4) {
            $this->debug('New probe instanciated');
            foreach ($this as $k => $v) {
                if ('options' !== $k && 'signedArgs' !== $k) {
                    if ('' !== $v = (string) $v) {
                        $this->debug('  '.$k.': '.$v);
                    }
                }
            }
        }
    }

    /**
     * Tells if the probe is cryptographically verified, i.e. if the signature in $query is valid.
     *
     * @return bool
     *
     * @api
     */
    public function isVerified()
    {
        return $this->box('doVerify', false);
    }

    public function setConfiguration($configuration)
    {
        if ($configuration === null) {
            $this->configuration = null;
        } else {
            $this->configuration = (string) $configuration;
        }
    }

    /**
     * Gets the response message/status/line.
     *
     * This lines gives details about the status of the probe. That can be:
     * - an error: `Blackfire-Error: $errNumber $urlEncodedErrorMessage`
     * - or not: `Blackfire-Response: $rfc1738EncodedMessage`
     *
     * @return string The response line
     *
     * @api
     */
    public function getResponseLine()
    {
        return $this->responseLine;
    }

    /**
     * Enables profiling instrumentation and data aggregation.
     *
     * One and only one probe can be enabled at the same time.
     *
     * @see getResponseLine() for error/status reporting
     *
     * @return bool False if enabling failed.
     *
     * @api
     */
    public function enable()
    {
        if ($this->autoEnabled) {
            $this->autoEnabled = false;
            $this->discard();
        }

        $enabled = $this->box('doEnable', false);

        if ($enabled && self::$probe === $this && null !== $this->outputStream) {
            self::boxPostEnable();
        }

        return $enabled;
    }

    /**
     * Discard collected data and disables instrumentation.
     *
     * Does not close the profile payload, allowing to re-enable the probe and aggregate data in the same profile.
     *
     * @return bool False if the probe was not enabled.
     *
     * @api
     */
    public function discard()
    {
        return $this->box('doDiscard', true);
    }

    /**
     * Disables profiling instrumentation and data aggregation.
     *
     * Does not close the profile payload, allowing to re-enable the probe and aggregate data in the same profile.
     * As a side-effect, flushes the collected profile to the output.
     *
     * @return bool False if the probe was not enabled.
     *
     * @api
     */
    public function disable()
    {
        return $this->box('doDisable', true, false);
    }

    /**
     * Disables and closes profiling instrumentation and data aggregation.
     *
     * Closing means that a later enable() will create a new profile on the output.
     * As a side-effect, flushes the collected profile to the output.
     *
     * @return bool False if the probe was not enabled.
     *
     * @api
     */
    public function close()
    {
        return $this->box('doDisable', true, true);
    }

    public static function addMarker($label = '')
    {
        $label = ''; // prevent OPcache optimization
    }

    // XXX
    // XXX - END OF PUBLIC API - XXX
    // XXX

    /**
     * @internal
     */
    private static function boxPostEnable()
    {
        if (!self::$shutdownFunctionRegistered) {
            self::$shutdownFunctionRegistered = true;

            register_shutdown_function(array(self::$probe, 'onShutdown'));
        }

        if (!headers_sent()) {
            header('X-'.self::$probe->getResponseLine());
        }
    }

    /**
     * @internal
     */
    private static function restoreErrorHandler()
    {
        restore_error_handler();
    }

    /**
     * @internal
     */
    public function __destruct()
    {
        $this->close();
    }

    /**
     * Wraps internal functions and handles any error/exception.
     *
     * @internal
     */
    private function box($method, $returnValue)
    {
        set_error_handler(__CLASS__.'::onInternalError');

        try {
            $args = func_get_args();
            unset($args[0], $args[1]);
            $this->debug('Boxing '.$method);
            $returnValue = call_user_func_array(array($this, $method), $args);
        } catch (Exception $e) {
            $this->warn(get_class($e).': '.$e->getMessage().' in '.$e->getFile().':'.$e->getLine());
            restore_error_handler();
            $this->profilerDisable();
            $this->responseLine = 'Blackfire-Error: 101 '.rawurlencode($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        }

        self::restoreErrorHandler();

        return $returnValue;
    }

    /**
     * @internal
     */
    private function doVerify()
    {
        if (null === $this->outputStream) {
            $signature = strtr($this->signature, '-_', '+/');
            $signature = base64_decode($signature, true);

            // XXX Crypto checks are done here in the C version.
            //     In the PHP version, this is delegated to the agent,
            //     no verification occurs when the output is a directory.

            $s = $this->signedArgs;

            if (!isset($s['expires'], $s['profileSlot'], $s['agentIds'], $s['userId'], $s['collabToken'])) {
                $this->info('Missing signed args: expires, profileSlot, agentIds, userId or collabToken');
            } elseif (time() > (int) $s['expires']) {
                $this->info('Expired signature');
            } elseif (!$signature) {
                $this->info('Invalid signature');
            } else {
                $this->debug('Signature looks OK');
                $this->openOutput();
            }
        }

        return (bool) $this->outputStream;
    }

    /**
     * @internal
     */
    private function doEnable()
    {
        if ($this->isEnabled) {
            return true;
        }
        if ($this->stale) {
            $this->responseLine = 'Blackfire-Error: 103 Samples quota is out';

            return false;
        }
        if (self::$profilerIsEnabled) {
            $this->responseLine = 'Blackfire-Error: 101 An other probe is already profiling';

            return false;
        }

        if ($this->doVerify()) {
            $this->writeChunkProlog();
            $this->profilerEnable();
            $this->isEnabled = true;
        }

        return $this->isEnabled;
    }

    /**
     * @internal
     */
    private function doDiscard()
    {
        if (!$this->isEnabled) {
            return false;
        }
        $this->isEnabled = false;
        $this->profilerDisable();

        return true;
    }

    /**
     * @internal
     */
    private function doDisable($close = false)
    {
        if (!$this->isEnabled) {
            return false;
        }
        $this->isEnabled = false;
        $this->profilerWrite(true);
        if ($close && $this->outputStream) {
            $this->debug('Closing output stream');
            flock($this->outputStream, LOCK_UN);
            fclose($this->outputStream);
            $this->outputStream = null;

            if (false === strpos($this->responseLine, 'continue=true')) {
                $this->stale = true;
            }
        }

        return true;
    }

    /**
     * @internal
     */
    private function openOutput()
    {
        if (null !== $this->outputStream) {
            return $this->outputStream;
        }

        $this->outputStream = false;
        $url = $this->agentSocket;
        $noop = $this->blackfireYmlAsked();

        if (($i = strpos($url, '://')) && in_array(substr($url, 0, $i), stream_get_transports(), true)) {
            $this->debug('Lets open '.$url);
            if ($h = stream_socket_client($url, $errno, $errstr, 0, STREAM_CLIENT_CONNECT | STREAM_CLIENT_ASYNC_CONNECT)) {
                stream_set_timeout($h, 0, $this->agentTimeout);
                stream_set_write_buffer($h, 0);
                $i = array(null, array($h), null);
                if (stream_select($i[0], $i[1], $i[2], 0, $this->agentTimeout)) {
                    $this->writeHelloProlog($h, $noop);
                    if (false !== $response = fgets($h, 4096)) {
                        $response = rtrim($response);

                        if (0 !== strpos($response, 'Blackfire-Response: ')) {
                            fclose($h);
                            $h = false;
                            if (0 !== strpos($response, 'Blackfire-Error: ')) {
                                $response = "Blackfire-Error: 102 Invalid agent response ($response)";
                            }
                        } else {
                            $features = null;
                            // Let's parse what is in "Blackfire-Response: " (20 chars)
                            parse_str(substr($response, 20), $features);
                            if (isset($features['blackfire_yml'])) {
                                $i = $this->getConfiguration();
                                self::fwrite($h, 'Blackfire-Yaml-Size: '.strlen($i)."\n".$i);
                            }
                            if (isset($features['composer_lock'])) {
                                $i = $this->getComposerLock();
                                self::fwrite($h, 'Composer-Lock-Size: '.strlen($i)."\n".$i);
                            }

                            while ('' !== rtrim(fgets($h, 4096))) {
                                // No-op (Blackfire-Keys, Blackfire-Fn-Args)
                            }
                        }
                    } else {
                        fclose($h);
                        $h = false;
                        $response = 'Blackfire-Error: 101 Agent connection timeout (read)';
                    }
                } else {
                    fclose($h);
                    $h = false;
                    $response = 'Blackfire-Error: 101 Agent connection timeout (write)';
                }
            } else {
                $response = "Blackfire-Error: 101 $errstr ($errno)";
            }
        } elseif ($i && 'blackfire' === substr($url, 0, $i)) {
            $this->debug('Lets open '.$url);
            $h = fopen($url, 'wb');
            $this->writeHelloProlog($h, $noop);

            $response = 'Blackfire-Response: continue=false';
        } else {
            $i = sprintf('%019.6F', microtime(true)).'-';
            $i .= substr(str_replace(array('+', '/'), array('', ''), base64_encode(md5(mt_rand(), true))), 0, 6);
            $url .= '/'.$i.'.log';

            $this->debug('Lets open '.$url);
            $h = fopen($url, 'wb');
            flock($h, LOCK_SH); // This shared lock allows readers to wait for the end of the stream
            stream_set_write_buffer($h, 0);

            $response = 'Blackfire-Response: continue=false';
        }

        $this->responseLine = $response;
        $this->outputStream = $h;

        $this->debug($response);

        if ($h && !$noop) {
            $this->writeMainProlog();
        }

        return $h;
    }

    /**
     * @internal
     */
    private function writeHelloProlog($h, $noop = false)
    {
        $hello = '';
        if ($this->envId && $this->envToken) {
            $line = $this->envId.':'.$this->envToken;
            if (strlen($line) !== strcspn($line, "\r\n") || 1 < substr_count($line, ':')) {
                $this->warn('Invalid env id/token');
            } else {
                $hello .= 'Blackfire-Auth: '.$line."\n";
            }
        }
        $line = 'signature='.$this->signature.'&aggreg_samples='.$this->aggregSamples;
        isset($this->challenge[0]) and $line = $this->challenge.'&'.$line;
        isset($this->profileTitle) and $line .= '&profile_title='.$this->profileTitle;
        isset($this->configYml) and $line .= '&config_yml='.$this->configYml;
        $hello .= 'Blackfire-Query: '.$line."\n";
        $hello .= sprintf('Blackfire-Probe: php-%s', PHP_VERSION);
        if ($this->options['blackfire_yml']) {
            $hello .= ', blackfire_yml';
        }
        if ($this->options['composer_lock']) {
            $hello .= ', composer_lock';
        }
        if ($this->options['timespan']) {
            $hello .= ', timespan';
        }
        if ($this->options['config']) {
            $hello .= ', config';
        }
        if ($noop) {
            $hello .= ', noop';
        }
        $hello .= "\n"; // End of Blackfire Probe
        $hello .= "\n"; // End of initialization

        self::fwrite($h, $hello);
    }

    /**
     * @internal
     */
    private function getConfiguration()
    {
        if ($this->configuration !== null) {
            return $this->configuration;
        }

        return $this->getRootFile('.blackfire.yml');
    }

    /**
     * @internal
     */
    private function getComposerLock()
    {
        return $this->getRootFile('composer.lock');
    }

    /**
     * @internal
     */
    private function getRootFile($file)
    {
        try {
            if (PHP_SAPI === 'cli-server') {
                $baseDir = $_SERVER['DOCUMENT_ROOT'];
            } else {
                $baseDir = dirname($_SERVER['SCRIPT_FILENAME']);
            }

            if ($dir = realpath($baseDir)) {
                do {
                    $prevDir = $dir;
                    $rootFile = $dir.DIRECTORY_SEPARATOR.$file;
                    $dir = dirname($dir);
                } while (!(file_exists($rootFile) && is_file($rootFile)) && $prevDir !== $dir);

                if ($prevDir !== $dir) {
                    $this->debug("Found $rootFile");

                    return file_get_contents($rootFile);
                }

                $this->debug(sprintf('No %s found', $file));
            } else {
                $this->debug('Realpath failed on '.$baseDir);
            }
        } catch (ErrorException $e) {
            $this->warn($e->getMessage().' in '.$e->getFile().':'.$e->getLine());
        }
    }

    /**
     * @internal
     */
    private function writeMainProlog()
    {
        // Loaded extensions list helps understanding runtime behavior
        $extensions = array();
        foreach (get_loaded_extensions() as $e) {
            $extensions[$e] = phpversion($e);
        }

        // Keep only keys from $_COOKIE
        $cookies = array_keys($_COOKIE);

        // Keep selected keys from $_SERVER
        $servers = array();
        foreach ($this->options['server_keys'] as $e) {
            if (isset($_SERVER[$e])) {
                $servers[$e] = $_SERVER[$e];
            }
        }

        // Get request's URI
        if (isset($_SERVER['HTTP_X_ORIGINAL_URL'])) {
            $e = $_SERVER['HTTP_X_ORIGINAL_URL'];
        } elseif (isset($_SERVER['HTTP_X_REWRITE_URL'])) {
            $e = $_SERVER['HTTP_X_REWRITE_URL'];
        } elseif (!empty($_SERVER['IIS_WasUrlRewritten']) && !empty($_SERVER['UNENCODED_URL'])) {
            $e = $_SERVER['UNENCODED_URL'];
        } elseif (isset($_SERVER['REQUEST_URI'][0])) {
            $e = $_SERVER['REQUEST_URI'];
            if ('/' !== $e[0]) {
                $e = preg_replace('#^https?://[^/]+#', '', $e);
            }
        } elseif (isset($_SERVER['ORIG_PATH_INFO'])) {
            $e = $_SERVER['ORIG_PATH_INFO'];
            if (!empty($_SERVER['QUERY_STRING'])) {
                $e .= '?'.$_SERVER['QUERY_STRING'];
            }
        } else {
            $e = '';
        }

        if (!empty($e)) {
            $servers['REQUEST_URI'] = $e;
        }

        self::fwrite($this->outputStream, 'file-format: '.$this->fileFormat."\n"
            .'probed-os: '.PHP_OS."\n"
            .'probed-language: php'."\n"
            .'probed-runtime: '.(defined('HHVM_VERSION') ? 'HHVM '.HHVM_VERSION : ('PHP '.PHP_VERSION)).' ('.PHP_SAPI.")\n"
            .'probed-features: '.((UPROFILER_FLAGS_CPU & $this->flags) ? 'flag_cpu=1&' : '').((UPROFILER_FLAGS_MEMORY & $this->flags) ? 'flag_memory=1&' : '')."\n"
            .'php-extensions: '.strtr(http_build_query($extensions, '', '&'), self::$urlEncMap)."\n"
            .'_COOKIE: '.strtr(http_build_query($cookies, '', '&'), self::$urlEncMap)."\n"
            .'_SERVER: '.strtr(http_build_query($servers, '', '&'), self::$urlEncMap)."\n"
            ."\nmain()//1 0 0 0 0\n\n"
        );

        $this->debug('Main prolog pushed');
    }

    /**
     * @internal
     */
    private function writeChunkProlog()
    {
        $data = 'request-mu: '.memory_get_usage(true)."\n"
            .'request-pmu: '.memory_get_peak_usage(true)."\n"
            .'request-start: '.microtime(true)."\n";

        if (function_exists('sys_getloadavg')) {
            $data .= 'sys-load-avg: '.implode(' ', sys_getloadavg())."\n";
        }

        self::fwrite($this->outputStream, $data);
    }

    /**
     * @internal
     */
    private function profilerEnable()
    {
        self::$profilerIsEnabled = true;

        if (is_string($this->profiler)) {
            $p = $this->profiler.'_enable';
            $this->debug($p);

            $p($this->flags, $this->options);
        } else {
            $this->info('No profiler to enable');
        }
    }

    /**
     * @internal
     */
    private function profilerDisable()
    {
        self::$profilerIsEnabled = false;

        if (is_string($this->profiler)) {
            $p = $this->profiler.'_disable';
            $this->debug($p);

            return $p();
        } elseif (is_array($this->profiler)) {
            $this->debug('data array profiler_disable');

            return $this->profiler;
        } else {
            $this->info('No profiler to disable');

            return array();
        }
    }

    /**
     * @internal
     */
    private function profilerWrite($disable, $chunk = '')
    {
        $data = $this->profilerDisable();

        $chunk .= 'request-end: '.microtime(true)
            ."\nrequest-mu: ".memory_get_usage(true)
            ."\nrequest-pmu: ".memory_get_peak_usage(true)
            ."\n\n";

        $this->debug('Pushing '.count($data).' call pairs');

        if (!$disable) {
            $this->profilerEnable();
        }
        $h = $this->outputStream;
        $i = 50; // 50 ~= 4Ko chunks

        // Speed optimized paths

        if (!$data) {
            // No-op
        } elseif ((UPROFILER_FLAGS_CPU & $this->flags) && (UPROFILER_FLAGS_MEMORY & $this->flags)) {
            foreach ($data as $k => $v) {
                $chunk .= "{$k}//{$v['ct']} {$v['wt']} {$v['cpu']} {$v['mu']} {$v['pmu']}\n";

                if (0 === --$i) {
                    self::fwrite($h, $chunk);
                    $chunk = '';
                    $i = 50;
                }
            }
        } elseif (UPROFILER_FLAGS_MEMORY & $this->flags) {
            foreach ($data as $k => $v) {
                $chunk .= "{$k}//{$v['ct']} {$v['wt']} 0 {$v['mu']} {$v['pmu']}\n";

                if (0 === --$i) {
                    self::fwrite($h, $chunk);
                    $chunk = '';
                    $i = 50;
                }
            }
        } elseif (UPROFILER_FLAGS_CPU & $this->flags) {
            foreach ($data as $k => $v) {
                $chunk .= "{$k}//{$v['ct']} {$v['wt']} {$v['cpu']} 0 0\n";

                if (0 === --$i) {
                    self::fwrite($h, $chunk);
                    $chunk = '';
                    $i = 50;
                }
            }
        } else {
            foreach ($data as $k => $v) {
                $chunk .= "{$k}//{$v['ct']} {$v['wt']} 0 0 0\n";

                if (0 === --$i) {
                    self::fwrite($h, $chunk);
                    $chunk = '';
                    $i = 50;
                }
            }
        }

        if (isset($data['main()'])) {
            $chunk .= "main()//-{$data['main()']['ct']} 0 0 0 0\n";
        }

        $chunk .= "\n";

        return self::fwrite($h, $chunk);
    }

    /**
     * @internal
     */
    private static function fwrite($stream, $data)
    {
        $len = strlen($data);
        $written = fwrite($stream, $data);

        if (false !== $written) {
            while ($written < $len) {
                fflush($stream);
                $w = fwrite($stream, substr($data, $written));
                $written += $w ? $w : $len + 1;
            }

            if ($written === $len) {
                return true;
            }
        }
    }

    /**
     * @internal
     */
    public static function onInternalError($type, $message, $file, $line)
    {
        throw new ErrorException($message, 0, $type, $file, $line);
    }

    /**
     * @internal
     */
    public static function onError()
    {
        return false; // Delegate error handling to the internal handler, but adds a line in profiler's data
    }

    /**
     * @internal
     */
    public function onException($e)
    {
        // Rethrow only, but adds a line in profiler's data
        $this->box('profilerWrite', null, true); // Prevents a crash with XHProf

        throw $e;
    }

    /**
     * @internal
     */
    public function onShutdown($extraHeaders = '')
    {
        $this->box('doShutdown', null, $extraHeaders);
    }

    private function blackfireYmlAsked()
    {
        return isset($_SERVER['REQUEST_METHOD']) && 'POST' === strtoupper($_SERVER['REQUEST_METHOD']) && false !== strpos($this->signedArgs['agentIds'], 'request-id-blackfire-yml');
    }

    /**
     * @internal
     */
    private function doShutdown($extraHeaders = '')
    {
        // Get and write data now so that any later fatal error
        // does not prevent collecting what we already have.

        if (!$this->isEnabled) {
            return;
        }

        $e = error_get_last();

        if (isset($e['type'])) {
            switch ($e['type']) {
                case E_ERROR:
                case E_PARSE:
                case E_USER_ERROR:
                case E_CORE_ERROR:
                case E_COMPILE_ERROR:
                case E_RECOVERABLE_ERROR:
                    $h = explode("\r", $e['message'], 2);
                    $h = explode("\n", $h[0], 2);
                    $h[1] = " in {$e['file']}:{$e['line']}";
                    $h[0] = str_replace($h[1], '', $h[0]);
                    $h = "fatal-error: {$h[0]}{$h[1]}\n";
                    $this->info('Got '.$h);
                    self::fwrite($this->outputStream, $h);

                    break;
            }
        }

        if (function_exists('http_response_code')) {
            $extraHeaders .= 'response-code: '.http_response_code()."\n";
        }
        $this->profilerWrite(false, $extraHeaders);
    }

    /**
     * @internal
     */
    private function warn($msg)
    {
        if ($this->logLevel >= 2) {
            file_put_contents($this->logFile, sprintf("[%3x] WARN: %s\n", $this->seqId, $msg), FILE_APPEND);
        }
    }

    /**
     * @internal
     */
    private function info($msg)
    {
        if ($this->logLevel >= 3) {
            file_put_contents($this->logFile, sprintf("[%3x] INFO: %s\n", $this->seqId, $msg), FILE_APPEND);
        }
    }

    /**
     * @internal
     */
    private function debug($msg)
    {
        if ($this->logLevel >= 4) {
            file_put_contents($this->logFile, sprintf("[%3x] DBUG: %s\n", $this->seqId, $msg), FILE_APPEND);
        }
    }
}
