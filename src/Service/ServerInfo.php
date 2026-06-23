<?php

namespace App\Service;

use Symfony\Component\HttpKernel\Kernel;

class ServerInfo
{
    /**
     * The deploy build timestamp.
     *
     * @return false|int|string
     */
    public function getBuild()
    {
        // This file is touch and timestamp updated every time the deploy script is executed.
        return filemtime(__DIR__ . '/../../.env') ?? '';
    }

    /**
     * The server name.
     *
     * @return mixed|string
     */
    public function getServerName()
    {
        return $_SERVER['SERVER_NAME'] ?? 'n/a';
    }

    /**
     * The server IP.
     *
     * @return mixed|string
     */
    public function getServerIP()
    {
        return $_SERVER['SERVER_ADDR'] ?? 'n/a';
    }

    /**
     * The PHP version.
     *
     * @return false|string
     */
    public function getPHPVersion()
    {
        return phpversion();
    }

    /**
     * The Symfony version.
     *
     * @return string
     */
    public function getSymfonyVersion(): string
    {
        return Kernel::VERSION;
    }
}
