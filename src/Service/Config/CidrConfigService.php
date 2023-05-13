<?php

namespace App\Service\Config;

use App\Service\OS\LocalUserService;
use Symfony\Component\Filesystem\Filesystem;

class CidrConfigService
{
    public function __construct(private readonly LocalUserService $localUserService)
    {
    }

    public function loadCidr(string $configFile): ?string
    {
        $configFile = $this->localUserService->expandHomeDirectory($configFile);
        if (!file_exists($configFile)) {
            return null;
        }

        return file_get_contents($configFile);
    }

    public function saveCidr(string $configFile, string $cidr): void
    {
        $configFile = $this->localUserService->expandHomeDirectory($configFile);

        $filesystem = new Filesystem();
        $filesystem->dumpFile($configFile, $cidr);
    }
}
