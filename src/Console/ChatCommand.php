<?php

declare(strict_types=1);

namespace Marwa\AI\Console;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ChatCommand extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setName('chat')
            ->setDescription('Start an interactive AI chat session')
            ->addOption('provider', 'p', InputOption::VALUE_REQUIRED, 'AI provider');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $ai = $this->getAI($input);
        $provider = $ai->getDefaultProvider();
        
        try {
            $client = $ai->driver($provider);
        } catch (\Throwable $e) {
            $output->writeln("<error>Error: {$e->getMessage()}</error>");
            return self::FAILURE;
        }

        $output->writeln("<info>Marwa AI Chat - {$provider}</info>");
        $output->writeln('Type "exit" to quit. Start with "system:" for system prompt.');
        $output->writeln('');

        $messages = [];

        while (true) {
            $output->write('<comment>You ></comment> ');
            $line = trim(fgets(STDIN));

            if ($line === 'exit' || $line === 'quit') {
                break;
            }

            if (str_starts_with($line, 'system:')) {
                $messages[] = ['role' => 'system', 'content' => trim(substr($line, 7))];
                $output->writeln('<comment>System prompt set.</comment>');
                continue;
            }

            if (empty($line)) {
                continue;
            }

            $messages[] = ['role' => 'user', 'content' => $line];

            try {
                $responseContent = '';
                $firstChunk = true;
                $spinnerIndex = 0;

                // Show spinner while waiting for first real token
                $stream = $client->streamCompletion($messages);
                
                foreach ($stream as $chunk) {
                    $delta = $chunk->getDelta();

                    if ($firstChunk && empty(trim($delta))) {
                        $this->renderSpinner($output, $spinnerIndex++);
                        continue;
                    }

                    if ($firstChunk && !empty(trim($delta))) {
                        $this->clearLine($output);
                        $output->write("<info>AI ></info> ");
                        $firstChunk = false;
                    }
                    
                    if (!$firstChunk) {
                        $responseContent .= $delta;
                        $output->write($delta);
                    }
                }
                
                $output->writeln('');
                $messages[] = ['role' => 'assistant', 'content' => $responseContent];
            } catch (\Throwable $e) {
                $this->clearLine($output);
                $output->writeln("<error>Error: {$e->getMessage()}</error>");
            }
        }

        return self::SUCCESS;
    }
}
