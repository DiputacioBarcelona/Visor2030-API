<?php

namespace App\Tests\Integration;

use App\Service\ServerInfo;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class ServerInfoServiceTest extends KernelTestCase
{
    public function testService(): void
    {
        // Tests that service works and returns valid data.
        self::bootKernel();
        $container = static::getContainer();
        $serverInfo = $container->get(ServerInfo::class);

        // Asserts can show symfony version, and it's expected version.
        $this->assertStringStartsWith('6.4.', $serverInfo->getSymfonyVersion());
    }
}
