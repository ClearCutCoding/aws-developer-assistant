<?php

namespace App\Service\Config;

use App\Service\OS\LocalUserService;
use Symfony\Component\Yaml\Yaml;

class ConfigLoader
{
    private const CONFIG_PATH = '~/.clearcutcoding/aws-developer-assistant/config.yaml';

    public function __construct(private readonly LocalUserService $localUserService)
    {
    }

    public function load(): array
    {
        $path = $this->localUserService->expandHomeDirectory(self::CONFIG_PATH);
        $config = Yaml::parseFile($path);

        return $config ?? [];
    }
}
