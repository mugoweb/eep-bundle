<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationDeleteCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:delete')
            ->setAliases(array('eep:lo:delete'))
            ->setDescription('Delete location subtree')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputUserId = $input->getOption('user-id');

        if ($inputLocationId)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
            $locationService = $repository->getLocationService();

            $location = $locationService->loadLocation($inputLocationId);

            $io = new SymfonyStyle($input, $output);
            $confirm = $input->getOption('no-interaction');
            if (!$confirm)
            {
                $confirm = $io->confirm(
                    sprintf(
                        'Are you sure you want to delete "%s" subtree (%d children)? This may take a while for subtrees with a large number of nested children',
                        $location->contentInfo->name,
                        $locationService->getLocationChildCount($location),
                        $location->contentInfo->name
                    ),
                    false
                );
            }

            if ($confirm)
            {
                try
                {
                    $locationService->deleteLocation($location);
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
        }
    }
}
