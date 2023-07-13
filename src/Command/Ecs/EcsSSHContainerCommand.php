<?php

namespace App\Command\Ecs;

use App\Command\CommandLogType;
use App\Command\Traits\CommandTrait;
use App\Service\AWS\Ec2HostRetriever;
use App\Service\AWS\EcsContainerRetriever;
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
 *   bin/console ecs:ssh:container --project abc.
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
class EcsSSHContainerCommand extends Command
{
    use CommandTrait;

    public function __construct(
        private readonly IpService $ipService,
        private readonly ConfigLoader $configLoader,
        private readonly CidrConfigService $cidrConfigService,
        private readonly SecurityGroupService $securityGroupService,
        private readonly Ec2HostRetriever $ec2HostRetriever,
        private readonly EcsContainerRetriever $ecsContainerRetriever
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setName('ecs:ssh:container')
            ->setDescription('')
            ->setDefinition(
                new InputDefinition([
                    new InputOption('project', null, InputOption::VALUE_REQUIRED),
                    new InputOption('service', null, InputOption::VALUE_OPTIONAL),
                ])
            );
    }

    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setupLogging(new SymfonyStyle($input, $output));

        $service = $input->getOption('service');

        $project = $input->getOption('project');
        if (!$project) {
            $this->log(CommandLogType::LOGTYPE_ERROR, 'project must be provided');
        }

        $this->process($project, $service, $input, $output);

        return 0;
    }

    private function process(string $project, ?string $service, InputInterface $input, OutputInterface $output): void
    {
        $config = $this->configLoader->load();

        $projectConfig = $config['ecs']['projects'][$project];
        if (empty($projectConfig)) {
            $this->log(CommandLogType::LOGTYPE_ERROR, 'config not found for project');
        }

        $hosts = $this->getHosts($projectConfig);
        $containers = $this->populateContainers($hosts, $service, $projectConfig);

        $selected = $this->askContainer($containers, $input, $output);
        $host = $selected['host']['dns'];
        $container = $selected['container']['id'];

        $cmd = sprintf('ssh -l ec2-user -i "%s" -t %s "docker exec -it %s bash"', $projectConfig['ssh_key_path'], $host, $container);
        echo $cmd;
    }

    private function getHosts(array $projectConfig): array
    {
        return $this->ec2HostRetriever->retrieve(
            $projectConfig['aws.profile'],
            $projectConfig['service.identifier']
        );
    }

    private function populateContainers(array $hosts, ?string $service, array $projectConfig): array
    {
        $containers = $this->ecsContainerRetriever->retrieve($hosts, $projectConfig['ssh_key_path'], true);

        if (!$service) {
            return $containers;
        }

        return array_values(array_filter(
            $containers,
            fn (array $container) => str_contains(strtolower((string) $container['container']['image']), strtolower($service))
        ));
    }

    /**
     * @psalm-suppress UndefinedInterfaceMethod
     */
    protected function askContainer(array $containers, InputInterface $input, OutputInterface $output): array
    {
        $choices = [];
        foreach ($containers as $i => $containerData) {
            $host = $containerData['host']['dns'];
            $id = $containerData['container']['id'];
            $image = $containerData['container']['image'];

            $choices["c{$i}"] = "{$host} > [{$id}] {$image}";
        }

        $helper = $this->getHelper('question');
        $question = new ChoiceQuestion(
            'Choose Host:',
            $choices
        );
        $question->setErrorMessage('Invalid choice');

        $selected = $helper->ask($input, $output, $question);

        return $containers[substr((string) $selected, 1)];
    }
}
