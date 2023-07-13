<?php

namespace App\Command\Ecs;

use App\Command\CommandLogType;
use App\Command\Traits\CommandTrait;
use App\Service\AWS\Ec2HostRetriever;
use App\Service\AWS\SecurityGroupService;
use App\Service\Config\CidrConfigService;
use App\Service\Config\ConfigLoader;
use App\Service\IpService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * Usage:
 *   bin/console ecs:ssh:instance --project abc.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EcsSSHInstanceCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly IpService $ipService,
        private readonly ConfigLoader $configLoader,
        private readonly CidrConfigService $cidrConfigService,
        private readonly SecurityGroupService $securityGroupService,
        private readonly Ec2HostRetriever $ec2HostRetriever
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('ecs:ssh:instance')
            ->setDescription('')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('project', null, InputOption::VALUE_REQUIRED),
                ])
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupLogging(new SymfonyStyle($input, $output));

        $project = $input->getOption('project');
        if (!$project) {
            $this->log(CommandLogType::LOGTYPE_ERROR, 'project must be provided');
        }

        $this->process($project, $input, $output);

        return 0;
    }

    private function process(string $project, InputInterface $input, OutputInterface $output): void
    {
        $config = $this->configLoader->load();

        $projectConfig = $config['ecs']['projects'][$project];
        if (empty($projectConfig)) {
            $this->log(CommandLogType::LOGTYPE_ERROR, 'config not found for project');
        }

        $hosts = $this->getHosts($projectConfig);
        $selected = $this->askHost($hosts, $input, $output);

        $cmd = sprintf('ssh -l ec2-user -i "%s" %s', $projectConfig['ssh_key_path'], $selected);
        echo $cmd;
    }

    private function getHosts(array $projectConfig): array
    {
        return $this->ec2HostRetriever->retrieve(
            $projectConfig['aws.profile'],
            $projectConfig['service.identifier']
        );
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function askHost(array $hosts, InputInterface $input, OutputInterface $output): string
    {
        $choices = [];
        foreach ($hosts as $i => $host) {
            $name = $host['name'];
            $dns = $host['dns'];

            $choices["c{$i}"] = "({$name}) {$dns}";
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Choose Host:',
            $choices
        );
        $question->setErrorMessage('Invalid choice');

        $selected = $helper->ask($input, $output, $question);

        return $hosts[substr((string) $selected, 1)]['dns'];
    }
}
