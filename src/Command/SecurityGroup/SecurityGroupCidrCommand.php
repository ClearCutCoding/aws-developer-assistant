<?php

namespace App\Command\SecurityGroup;

use App\Command\CommandLogType;
use App\Command\Traits\CommandTrait;
use App\Service\AWS\SecurityGroupService;
use App\Service\Config\CidrConfigService;
use App\Service\Config\ConfigLoader;
use App\Service\IpService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Usage:
 *   bin/console security-group:cidr.
 *
 *   bin/console security-group:cidr --project abc.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class SecurityGroupCidrCommand extends Command
{
    use CommandTrait;

    private IpService $ipService;

    private ConfigLoader $configLoader;

    private CidrConfigService $cidrConfigService;

    private SecurityGroupService $securityGroupService;

    public function __construct(
        IpService $ipService,
        ConfigLoader $configLoader,
        CidrConfigService $cidrConfigService,
        SecurityGroupService $securityGroupService
    ) {
        $this->ipService = $ipService;
        $this->configLoader = $configLoader;
        $this->cidrConfigService = $cidrConfigService;
        $this->securityGroupService = $securityGroupService;

        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('security-group:cidr')
            ->setDescription('')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('project', null, InputOption::VALUE_OPTIONAL),
                ])
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupLogging(new SymfonyStyle($input, $output));

        $project = $input->getOption('project');

        $this->process($project);

        return 0;
    }

    private function process(?string $project): void
    {
        $config = $this->configLoader->load();
        $cidrSavePath = $config['security-group-cidr']['cidr.save_path'];

        if ($project !== null) {
            $project = strtolower($project);
            $projectConfig = $config['security-group-cidr']['projects'][$project] ?? [];
            if (empty($projectConfig)) {
                $this->log(CommandLogType::LOGTYPE_ERROR, 'config not found for project');
            }

            $this->processProject($project, $projectConfig, $cidrSavePath);

            return;
        }

        foreach ($config['security-group-cidr']['projects'] as $project => $projectConfig) {
            $project = strtolower($project);
            $this->processProject($project, $projectConfig, $cidrSavePath);
        }
    }

    private function processProject(string $project, array $projectConfig, string $cidrSavePath): void
    {
        $this->log(CommandLogType::LOGTYPE_CAUTION, 'Processing project: ' . $project);

        $ip = $this->ipService->getIpAddress();
        if (!$ip) {
            $this->log(CommandLogType::LOGTYPE_ERROR, 'cannot determine ip');
        }

        $cidrConfigFile = $cidrSavePath . '/' . $project;

        $oldCidr = $this->cidrConfigService->loadCidr($cidrConfigFile);
        if ($oldCidr) {
            foreach ($this->securityGroupService->removeSecurityGroupEntry($oldCidr, $projectConfig) as $message) {
                echo $message;
            }
        }

        $newCidr = $ip . '/32';

        foreach ($this->securityGroupService->addSecurityGroupEntry($newCidr, $projectConfig) as $message) {
            echo $message;
        }

        $this->cidrConfigService->saveCidr($cidrConfigFile, $newCidr);
    }
}
