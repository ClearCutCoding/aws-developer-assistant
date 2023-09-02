<?php

namespace App\Service\AWS;

class Ec2HostRetriever
{
    protected ?string $ec2Name = null;

    protected ?string $awsCliProfile = null;

    public function __construct()
    {
    }

    public function retrieve(
        string $awsCliProfile,
        string $ec2Name
    ): array {
        $this->ec2Name = $ec2Name;
        $this->awsCliProfile = $awsCliProfile;

        return $this->getHosts();
    }

    /**
     * @psalm-suppress ForbiddenCode
     */
    private function getHosts(): array
    {
        $hosts = [];
        $profile = $this->awsCliProfile;

        $json = shell_exec("aws ec2 describe-instances --profile {$profile}");
        $instances = json_decode($json ?? '', null, 512, JSON_THROW_ON_ERROR);

        foreach ($instances->Reservations as $reservation) {
            foreach ($reservation->Instances as $instance) {
                $instanceName = '';

                foreach ($instance->Tags as $tag) {
                    if ($tag->Key === 'Name') {
                        $instanceName = $tag->Value;
                    }
                }

                if ($this->ec2Name && $instanceName !== $this->ec2Name) {
                    continue;
                }

                $hosts[] = [
                    'name' => $instanceName,
                    'dns' => $instance->PublicDnsName,
                ];
            }
        }

        return $hosts;
    }
}
