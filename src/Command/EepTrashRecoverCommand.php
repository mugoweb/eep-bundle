<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\TrashService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepTrashRecoverCommand extends Command
{
    public function __construct
    (
        TrashService $trashService,
        LocationService  $locationService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->trashService = $trashService;
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
            ->setName('eep:trash:recover')
            ->setAliases(array('eep:tr:recover'))
            ->setDescription('Recovers a trashed item')
            ->addArgument('trash-item-id', InputArgument::REQUIRED, 'Trash item id')
            ->addOption('parent-location-id', 'p', InputOption::VALUE_OPTIONAL, 'Parent location id for recovery operation', false)
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputTrashItemId = $input->getArgument('trash-item-id');
        $inputUserId = $input->getOption('user-id');
        $inputParentLocationId = $input->getOption('parent-location-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $trashItem = $this->trashService->loadTrashItem($inputTrashItemId);
        $parentLocation = ($inputParentLocationId)? $this->locationService->loadLocation($inputParentLocationId) : null;

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to recover "%s"%s?',
                    $trashItem->contentInfo->name,
                    ($inputParentLocationId)? ' at location "' . $parentLocation->contentInfo->name . '"' : ''
                ),
                false
            );
        }

        if ($confirm)
        {
            $loggerContext = array
            (
                $inputTrashItemId,
                $inputUserId,
                $inputParentLocationId
            );
            $this->logger->info($this->getName() . " confirmed", $loggerContext);

            try
            {
                $this->trashService->recover($trashItem, $parentLocation);

                $io->success('Recovery successful');
                $this->logger->info($this->getName() . " successful");
            }
            catch(Exceptions\UnauthorizedException $e)
            {
                $io->error($e->getMessage());
                $this->logger->error($this->getName() . " error", array($e->getMessage()));
            }
        }
        else
        {
            $io->writeln('Recovery cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
