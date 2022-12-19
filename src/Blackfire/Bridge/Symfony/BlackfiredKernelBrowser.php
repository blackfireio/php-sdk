<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Blackfire\Bridge\Symfony;

use Blackfire\Build\BuildHelper;
use Blackfire\Client as BlackfireClient;
use Blackfire\Profile\Configuration;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\BrowserKit\History;
use Symfony\Component\HttpKernel\KernelInterface;

class BlackfiredKernelBrowser extends KernelBrowser
{
    /**
     * @var BuildHelper
     */
    private $buildHelper;

    /**
     * @var BlackfireClient
     */
    private $blackfire;

    private $blackfireEnabled = false;

    public function __construct(KernelInterface $kernel, array $server = array(), History $history = null, CookieJar $cookieJar = null)
    {
        parent::__construct($kernel, $server, $history, $cookieJar);

        $this->buildHelper = BuildHelper::getInstance();
        $this->blackfire = $this->buildHelper->getBlackfireClient();
    }

    public function enableBlackfire(): void
    {
        $this->blackfireEnabled = true;
    }

    public function disableBlackfire(): void
    {
        $this->blackfireEnabled = false;
    }

    public function isBlackfireEnabled(): bool
    {
        return $this->blackfireEnabled;
    }

    protected function doRequest($request)
    {
        if ($this->blackfireEnabled) {
            $profileConfig = (new Configuration())
                ->setMetadata('skip_timeline', 'false')
                ->setTitle(sprintf('%s - %s', $request->getPathInfo(), $request->getMethod()));
            if ($this->buildHelper->hasCurrentScenario()) {
                $profileConfig->setScenario($this->buildHelper->getCurrentScenario());
            }

            $_SERVER += array(
                'HTTP_HOST' => 'localhost',
                'REQUEST_URI' => $request->getPathInfo(),
                'HTTP_USER_AGENT' => 'BlackfireKernelBrowser',
                'REQUEST_METHOD' => $request->getMethod(),
            );
            $probe = $this->blackfire->createProbe($profileConfig);
        }

        try {
            return parent::doRequest($request);
        } finally {
            if (isset($probe)) {
                $this->blackfire->endProbe($probe);
            }
        }
    }
}
