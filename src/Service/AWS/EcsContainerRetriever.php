<?php

namespace App\Service\AWS;

class EcsContainerRetriever
{
    protected ?string $keyPath = null;

    public function __construct()
    {
    }

    public function retrieve(
        array $hosts,
        string $keyPath,
        bool $flattened = false
    ): array {
        $this->keyPath = $keyPath;
        $hosts = $this->populateContainers($hosts);

        if ($flattened) {
            $flattened = $this->getFlattenedMap($hosts);

            return $this->sortFlattenedMap($flattened);
        }

        return $hosts;
    }

    private function getFlattenedMap(array $hosts): array
    {
        $flattenedMap = [];

        foreach ($hosts as $hostData) {
            $clonedHostData = $hostData;
            unset($clonedHostData['containers']);

            foreach (($hostData['containers'] ?? []) as $containers) {
                $flattenedMap[] = [
                    'host' => $clonedHostData,
                    'container' => $containers,
                ];
            }
        }

        return $flattenedMap;
    }

    private function sortFlattenedMap(array $containerData): array
    {
        usort($containerData, fn ($a, $b) => strcasecmp((string) $a['container']['image'], (string) $b['container']['image']));

        return $containerData;
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    private function populateContainers(array $hosts): array
    {
        foreach ($hosts as &$host) {
            $host['containers'] = $this->getContainersForHost($host['dns']);
        }

        return $hosts;
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    private function getContainersForHost(string $host): array
    {
        $containers = [];

        $keyPath = $this->keyPath;

        $ssh1 = `ssh -l ec2-user -i "{$keyPath}" -qt {$host} 'docker ps'`;
        $lines = explode("\n", (string) $ssh1);
        array_shift($lines);
        array_pop($lines);

        foreach ($lines as $line) {
            $parts = preg_split('~  +~', $line);

            if ((is_countable($parts) ? count($parts) : 0) < 2) {
                continue;
            }

            $id = $parts[0];
            $image = $parts[1];

            $containers[] = [
                'id' => $id,
                'image' => $image,
            ];
        }

        return $containers;
    }
}
