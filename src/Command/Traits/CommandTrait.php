<?php

namespace App\Command\Traits;

use App\Command\CommandLogType;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

trait CommandTrait
{
    protected SymfonyStyle $io;

    protected function setupLogging(SymfonyStyle $io): void
    {
        $this->io = $io;
    }

    protected function log(
        string $type,
        string $message,
        ?int $verbosity = null
    ): void {
        $message = implode(' :: ', [$this->getName(), $type, $message]);

        if (null !== $verbosity) {
            $this->io->writeln($message, $verbosity);

            return;
        }

        switch ($type) {
            case CommandLogType::LOGTYPE_ERROR:
                $this->io->error($message);
                exit;
            case CommandLogType::LOGTYPE_SUCCESS:
                $this->io->success($message);
                break;
            case CommandLogType::LOGTYPE_CAUTION:
                $this->io->caution($message);
                break;
            case CommandLogType::LOGTYPE_COMMENT:
            default:
                $this->io->comment($message);
                break;
        }
    }

    protected function logWithTimestamp(
        string $type,
        string $message,
        int $verbosity = null
    ): void {
        if (null === $verbosity) {
            $verbosity = OutputInterface::VERBOSITY_NORMAL;
        }

        $typeMapping = [
            CommandLogType::LOGTYPE_SUCCESS => 'info',
            CommandLogType::LOGTYPE_CAUTION => 'comment',
            CommandLogType::LOGTYPE_ERROR => 'error',
            CommandLogType::LOGTYPE_COMMENT => '',
        ];

        $typeTag = ($typeMapping[$type] ?? 'info');

        $now = new \DateTime();
        $now = $now->format('Y-m-d H:i:s');

        $message = implode(' - ', [$now, $this->getName(), $type, $message]);

        if (!empty($typeTag)) {
            $message = '<' . $typeTag . '>' . $message . '</' . $typeTag . '>';
        }

        $this->io->writeln($message, $verbosity);
    }
}
