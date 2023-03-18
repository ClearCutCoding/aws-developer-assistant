<?php

namespace App\Service;

class IpService
{
    public function __construct()
    {
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    public function getIpAddress(): string
    {
        $ip = shell_exec('curl --silent --show-error https://ipinfo.io/ip');

        return trim($ip ?? '');
    }
}
