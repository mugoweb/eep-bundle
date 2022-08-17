<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationRevealCommand extends Command
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
            ->setName('eep:location:reveal')
            ->setAliases(array('eep:lo:reveal'))
            ->setDescription('Reveal location subtree')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
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
                    'Are you sure you want to reveal "%s" subtree (%d children)? This may take a while for subtrees with a large number of nested children',
                    $location->contentInfo->name,
                    $this->locationService->getLocationChildCount($location)
                ),
                false
            );
        }

        if ($confirm)
        {
            try
            {
                $this->locationService->unhideLocation($location);
            }
            catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
            {
                $io->error($e->getMessage());
            }

            $io->success('Reveal successful');
        }
        else
        {
            $io->writeln('Reveal cancelled by user action');
        }
    }
}
