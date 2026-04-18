<?php

declare(strict_types=1);

namespace Marwa\AI\Console;

use Marwa\AI\MarwaAI;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

abstract class BaseCommand extends Command
{
    protected function getAI(InputInterface $input): MarwaAI
    {
        $ai = MarwaAI::instance();
        
        if ($input->hasOption('provider')) {
            $provider = $input->getOption('provider');
            if ($provider) {
                $ai->setDefaultProvider($provider);
            }
        }
        
        return $ai;
    }

    protected function clearLine(OutputInterface $output): void
    {
        $output->write("\r\x1B[K");
    }

    protected function renderSpinner(OutputInterface $output, int $index, string $message = 'Thinking'): void
    {
        $frames = ['⠋', '⠙', '⠹', '⠸', '⠼', '⠴', '⠦', '⠧', '⠇', '⠏'];
        $frame = $frames[$index % count($frames)];
        $output->write("\r<info>{$frame} {$message}...</info>");
    }
}
