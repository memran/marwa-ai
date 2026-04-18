<?php

declare(strict_types=1);

namespace Marwa\AI\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ProvidersCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('providers')
            ->setDescription('List available providers');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $providers = $this->getAI($input)->getAvailableProviders();
        $output->writeln('Available providers:');
        foreach ($providers as $p) {
            $output->writeln("  - <info>{$p}</info>");
        }
        return self::SUCCESS;
    }
}
