<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentDeleteCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        LocationService $locationService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
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
            ->setName('eep:content:delete')
            ->setAliases(array('eep:co:delete'))
            ->setDescription('Delete content')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentInfo = $this->contentService->loadContentInfo($inputContentId);
        $location = $this->locationService->loadLocation($contentInfo->mainLocationId);

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to delete "%s" subtree (%d children)? This may take a while for subtrees with a large number of nested children',
                    $contentInfo->name,
                    $this->locationService->getLocationChildCount($location)
                ),
                false
            );
        }

        if ($confirm)
        {
            try
            {
                $this->contentService->deleteContent($contentInfo);
            }
            catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
            {
                $io->error($e->getMessage());
            }

            $io->success('Delete successful');
        }
        else
        {
            $io->writeln('Delete cancelled by user action');
        }

        return Command::SUCCESS;
    }
}