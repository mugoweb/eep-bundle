<?php

namespace Eep\Bundle\Command;

use eZ\Publish\Core\Base\Exceptions\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationCopyCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:copy')
            ->setAliases(array('eep:lo:copy'))
            ->setDescription('Copy source location to be child of target location')
            ->addArgument('sourceLocationId', InputArgument::REQUIRED, 'Source location id')
            ->addArgument('targetLocationId', InputArgument::REQUIRED, 'Target location id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSourceLocationId = $input->getArgument('sourceLocationId');
        $inputTargetLocationId = $input->getArgument('targetLocationId');
        $inputUserId = $input->getOption('user-id');

        if ($inputSourceLocationId && $inputTargetLocationId)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
            $locationService = $repository->getLocationService();
            $contentTypeService = $repository->getContentTypeService();

            $sourceLocation = $locationService->loadLocation($inputSourceLocationId);
            $targetLocation = $locationService->loadLocation($inputTargetLocationId);
            if (stripos($targetLocation->pathString, $sourceLocation->pathString) !== false)
            {
                throw new InvalidArgumentException('targetLocationId', 'Target location is a sub location of the source subtree');
            }

            $targetContentType = $contentTypeService->loadContentType($targetLocation->getContentInfo()->contentTypeId);
            if (!$targetContentType->isContainer)
            {
                throw new InvalidArgumentException('targetLocationId', 'Cannot copy location to a parent that is not a container');
            }

            $io = new SymfonyStyle($input, $output);
            $confirm = $input->getOption('no-interaction');
            if (!$confirm)
            {
                $confirm = $io->confirm(
                    sprintf(
                        'Are you sure you want to copy "%s" subtree ( %d children) into "%s"? This may take a while for subtrees with a large number of nested children',
                        $sourceLocation->contentInfo->name,
                        $locationService->getLocationChildCount($sourceLocation),
                        $targetLocation->contentInfo->name
                    ),
                    false
                );
            }

            if ($confirm)
            {
                try
                {
                    $locationService->copySubtree($sourceLocation, $targetLocation);
                }
                catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
                {
                    $io->error( $e->getMessage() );
                }

                $io->success('Copy successful');
            }
            else
            {
                $io->writeln('Copy cancelled by user action');
            }
        }
    }
}
