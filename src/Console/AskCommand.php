<?php

declare(strict_types=1);

namespace Marwa\AI\Console;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class AskCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('ask')
            ->setDescription('Ask a single question')
            ->addArgument('prompt', InputArgument::REQUIRED, 'The question to ask')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $prompt = $input->getArgument('prompt');
        $ai = $this->getAI($input);
        
        try {
            $responseContent = '';
            $firstChunk = true;
            $spinnerIndex = 0;

            // Simple fake animation start to show immediate response
            for ($i = 0; $i < 5; $i++) {
                $this->renderSpinner($output, $spinnerIndex++);
                usleep(50000);
            }

            foreach ($ai->conversation($prompt)->stream() as $chunk) {
                $delta = $chunk->getDelta();
                
                // Keep spinning while chunks are empty (thinking/buffering)
                if ($firstChunk && empty(trim($delta))) {
                    $this->renderSpinner($output, $spinnerIndex++);
                    continue;
                }

                if ($firstChunk && !empty(trim($delta))) {
                    $this->clearLine($output);
                    $firstChunk = false;
                }

                if (!$firstChunk) {
                    $responseContent .= $delta;
                    $output->write($delta);
                }
            }

            $output->writeln('');
        } catch (\Throwable $e) {
            $this->clearLine($output);
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
