<?php

namespace App\Tests\Unit;

use App\Service\ServerInfo;
use PHPUnit\Framework\TestCase;

class ServerInfoTest extends TestCase
{
    public function testMethods(): void
    {
        $serverInfo = new ServerInfo();

        // Asserts methods exists and return data.
        $this->assertNotNull($serverInfo->getBuild());
        $this->assertNotNull($serverInfo->getServerName());
        $this->assertNotNull($serverInfo->getServerIP());

        // Asserts PHP version can be recovered, and it's a valid version.
        $this->assertTrue(version_compare($serverInfo->getPHPVersion(), '8.2', '>='));
    }
}
