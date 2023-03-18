<?php

namespace App\Service\OS;

class LocalUserService
{
    public function __construct()
    {
    }

    protected function getLocalUserInfo(): ?array
    {
        if (function_exists('posix_getuid')) {
            return posix_getpwuid(posix_getuid());
        }

        return null;
    }

    public function expandHomeDirectory(string $file): string
    {
        if (($info = $this->getLocalUserInfo()) && str_contains($file, '~')) {
            $file = str_replace('~', $info['dir'], $file);
        }

        return $file;
    }
}
