<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentLocationCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:location')
            ->setAliases(array('eep:co:lo', 'eep:co:location'))
            ->setDescription('Returns main location id by content id')
            ->addArgument('contentId', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('no-newline', 'x', null, 'Output without newline')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('contentId');
        $inputUserId = $input->getOption('user-id');

        if ($inputContentId)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));

            $content = $repository->getContentService()->loadContent($inputContentId);

            $io = new SymfonyStyle($input, $output);
            if ($input->getOption('no-newline'))
            {
                $io->write($content->contentInfo->mainLocationId);
            }
            else
            {
                $io->writeln($content->contentInfo->mainLocationId);
            }
        }
    }
}
