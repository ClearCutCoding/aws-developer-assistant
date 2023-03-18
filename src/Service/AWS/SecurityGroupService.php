<?php

namespace App\Service\AWS;

class SecurityGroupService
{
    public function __construct()
    {
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    public function addSecurityGroupEntry(string $cidr, array $projectConfig): \Generator
    {
        foreach ($projectConfig['security_groups'] as $group) {
            $cmd = 'aws ec2 authorize-security-group-ingress --profile ' . $projectConfig['aws.profile'] .
                ' --region ' . $group['region'] .
                ' --group-name=' . $group['id'] . ' --protocol tcp --port ' . $group['port'] . ' --cidr ' . $cidr;

            shell_exec($cmd);

            yield "ADDED {$cidr} TO {$group['id']} ON PORT {$group['port']} ({$group['region']})" . PHP_EOL;
        }
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    public function removeSecurityGroupEntry(string $cidr, array $projectConfig): \Generator
    {
        foreach ($projectConfig['security_groups'] as $group) {
            $cmd = 'aws ec2 revoke-security-group-ingress --profile ' . $projectConfig['aws.profile'] .
                ' --region ' . $group['region'] .
                ' --group-name=' . $group['id'] . ' --protocol tcp --port ' . $group['port'] . ' --cidr ' . $cidr;

            shell_exec($cmd);

            yield "REMOVED {$cidr} FROM {$group['id']} ON PORT {$group['port']} ({$group['region']})" . PHP_EOL;
        }
    }
}
