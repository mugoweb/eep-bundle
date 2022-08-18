<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Exceptions;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationCopyCommand extends Command
{
    public function __construct
    (
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
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
            ->setName('eep:location:copy')
            ->setAliases(array('eep:lo:copy'))
            ->setDescription('Copy source location to be child of target location')
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
        if (stripos($targetLocation->pathString, $sourceLocation->pathString) !== false)
        {
            throw new InvalidArgumentException('target-location-id', 'Target location is a sub location of the source subtree');
        }

        $targetContentType = $this->contentTypeService->loadContentType($targetLocation->getContentInfo()->contentTypeId);
        if (!$targetContentType->isContainer)
        {
            throw new InvalidArgumentException('target-location-id', 'Cannot copy location to a parent that is not a container');
        }

        $io = new SymfonyStyle($input, $output);
        $confirm = $input->getOption('no-interaction');
        if (!$confirm)
        {
            $confirm = $io->confirm(
                sprintf(
                    'Are you sure you want to copy "%s" subtree ( %d children) into "%s"? This may take a while for subtrees with a large number of nested children',
                    $sourceLocation->contentInfo->name,
                    $this->locationService->getLocationChildCount($sourceLocation),
                    $targetLocation->contentInfo->name
                ),
                false
            );
        }

        if ($confirm)
        {
            try
            {
                $this->locationService->copySubtree($sourceLocation, $targetLocation);

                $io->success('Copy successful');
            }
            catch(UnauthorizedException $e)
            {
                $io->error($e->getMessage());
            }
        }
        else
        {
            $io->writeln('Copy cancelled by user action');
        }

        return Command::SUCCESS;
    }
}
