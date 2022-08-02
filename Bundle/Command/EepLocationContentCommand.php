<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationContentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:content')
            ->setAliases(array('eep:lo:co', 'eep:lo:content'))
            ->setDescription('Returns content id by location id')
            ->addArgument('locationId', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('no-newline', 'x', null, 'output without newline')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputLocationId = $input->getArgument('locationId');
        $inputUserId = $input->getOption('user-id');

        if ($inputLocationId)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
            $location = $repository->getLocationService()->loadLocation($inputLocationId);

            $io = new SymfonyStyle($input, $output);
            if ($input->getOption('no-newline'))
            {
                $io->write($location->contentId);
            }
            else
            {
                $io->writeln($location->contentId);
            }
        }
    }
}
