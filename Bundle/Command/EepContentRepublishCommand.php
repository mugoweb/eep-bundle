<?php

namespace Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentRepublishCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:republish')
            ->setAliases(array('eep:co:republish'))
            ->setDescription('Re-publishes content by id')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));

        $contentService = $repository->getContentService();
        $contentInfo = $contentService->loadContentInfo($inputContentId);
        $contentDraft = $contentService->createContentDraft($contentInfo);

        $published = $contentService->publishVersion($contentDraft->versionInfo);

        $report = ($published)? "Republished {$inputContentId}" : "Failed to republish {$inputContentId}";

        $io = new SymfonyStyle($input, $output);
        $io->writeln($report);
    }
}
