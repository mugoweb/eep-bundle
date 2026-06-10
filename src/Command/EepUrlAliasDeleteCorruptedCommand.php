<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasDeleteCorruptedCommand extends Command
{
    public function __construct
    (
        private readonly URLAliasService $urlAliasService,
        private readonly PermissionResolver $permissionResolver,
        private readonly UserService $userService,
        private readonly EepLogger $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:urlalias:deletecorrupted')
            ->setAliases(array('eep:ua:deletecorrupted'))
            ->setDescription('Deletes corrupted URL aliases')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                'Are you sure you want to delete corrupted URL aliases?',
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $deletedCount = $this->urlAliasService->deleteCorruptedUrlAliases();

                $io->success(sprintf('Delete successful. deletedCount: %d', $deletedCount));
                $this->logger->info($this->getName() . " successful", array($deletedCount));
            }
            catch
            (
                Exceptions\InvalidArgumentException |
                Exceptions\UnauthorizedException
                $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Delete cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
