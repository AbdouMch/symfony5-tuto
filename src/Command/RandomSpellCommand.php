<?php

namespace App\Command;

use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class RandomSpellCommand extends Command
{
    protected static $defaultName = 'app:random-spell';
    protected static $defaultDescription = 'Cast a random spell';
    private LoggerInterface $spellCommandLogger;

    public function __construct(LoggerInterface $spellCommandLogger)
    {
        parent::__construct();
        $this->spellCommandLogger = $spellCommandLogger;
    }

    protected function configure(): void
    {
        $this
            ->addArgument('your-name', InputArgument::OPTIONAL, 'Your name')
            ->addOption('yell', null, InputOption::VALUE_NONE, 'Yell!!!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $youName = $input->getArgument('your-name');

        if ($youName) {
            $io->note(sprintf('Hello: %s', $youName));
        }
        $spells = [
            'alohomora',
            'confundo',
            'engorgio',
            'expecto patronum',
            'expelliarmus',
            'impedimenta',
            'reparo',
        ];

        $spell = $spells[array_rand($spells)];

        if ($input->getOption('yell')) {
            $spell = strtoupper($spell);
        }
        $this->spellCommandLogger->info("casting a spell for $youName", [$spell]);
        $io->success($spell);

        return Command::SUCCESS;
    }
}
