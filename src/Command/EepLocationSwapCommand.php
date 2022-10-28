<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationSwapCommand extends Command
{
    public function __construct
    (
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->locationService = $locationService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->logger = $logger;

        parent::__construct();
    }

    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:swap')
            ->setAliases(array('eep:lo:swap'))
            ->setDescription('Swap source- and target locations')
            ->addArgument('source-location-id', InputArgument::REQUIRED, 'Source location id')
            ->addArgument('target-location-id', InputArgument::REQUIRED, 'Target location id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSourceLocationId = $input->getArgument('source-location-id');
        $inputTargetLocationId = $input->getArgument('target-location-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $sourceLocation = $this->locationService->loadLocation($inputSourceLocationId);
        $targetLocation = $this->locationService->loadLocation($inputTargetLocationId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to swap locations "%s" and "%s"?',
                    $sourceLocation->contentInfo->name,
                    $targetLocation->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputSourceLocationId,
                $inputTargetLocationId,
                $inputUserId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->locationService->swapLocation($sourceLocation, $targetLocation);

                $io->success('Swap successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch(UnauthorizedException $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Swap cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
