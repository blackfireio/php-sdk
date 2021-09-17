<?php

/*
 * This file is part of the Blackfire SDK package.
 *
 * (c) Blackfire <support@blackfire.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Tests\Unit\Blackfire\Profile;

use Blackfire\Profile\Assertion\AssertionsBuilderInterface;
use Blackfire\Profile\Configuration;
use PHPUnit\Framework\TestCase;

class ConfigurationTest extends TestCase
{
    public function testGettingCustomAssertionsBuilder()
    {
        $assertionsBuilderStub = $this->createStub(AssertionsBuilderInterface::class);

        $configuration = new Configuration(null, $assertionsBuilderStub);

        self::assertEquals($assertionsBuilderStub, $configuration->getAssertionsBuilder());
    }

    public function testGettingDefaultAssertionsBuilder()
    {
        $configuration = new Configuration();

        self::assertTrue($configuration->getAssertionsBuilder() instanceof AssertionsBuilderInterface);
    }
}
