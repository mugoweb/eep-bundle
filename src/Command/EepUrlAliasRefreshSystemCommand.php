<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasRefreshSystemCommand extends Command
{
    public function __construct
    (
        private readonly URLAliasService $urlAliasService,
        private readonly LocationService $locationService,
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
            ->setName('eep:urlalias:refreshsystem')
            ->setAliases(array('eep:ua:refreshsystem'))
            ->setDescription('Refreshes system URL aliases for a location')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $location = $this->locationService->loadLocation($inputLocationId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to refresh system URL aliases for "%s"?',
                    $location->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputLocationId,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->urlAliasService->refreshSystemUrlAliasesForLocation($location);

                $io->success(sprintf('Refresh successful. locationId: %s', $inputLocationId));
                $this->logger->info($this->getName() . " successful", array($inputLocationId));
            }
            catch
            (
                Exceptions\InvalidArgumentException |
                Exceptions\UnauthorizedException |
                Exceptions\NotFoundException
                $e
            )
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Refresh cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
