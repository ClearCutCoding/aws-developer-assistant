<?php

namespace App\Service\Config;

use App\Service\OS\LocalUserService;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader
{
    private const CONFIG_PATH = '~/.clearcutcoding/aws-developer-assistant/config.yaml';

    private LocalUserService $localUserService;

    public function __construct(
        LocalUserService $localUserService
    ) {
        $this->localUserService = $localUserService;
    }

    public function load(): array
    {
        $path = $this->localUserService->expandHomeDirectory(self::CONFIG_PATH);
        $config = Yaml::parseFile($path);

        return $config ?? [];
    }
}
