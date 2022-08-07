<?php

namespace Eep\Bundle\Command;

use eZ\Publish\API\Repository\Values\Content\Query\Criterion\SectionId;
use Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepSectionAssignContentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:section:assigncontent')
            ->setAliases(array('eep:se:assigncontent'))
            ->setDescription('Assign content to section')
            ->addArgument('section-identifier', InputArgument::REQUIRED, 'Section identifier')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSectionIdentifier = $input->getArgument('section-identifier');
        $inputContentId = $input->getArgument( 'content-id' );
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
        $contentService = $repository->getContentService();
        $sectionService = $repository->getSectionService();

        $io = new SymfonyStyle($input, $output);
        try
        {
            $section = $sectionService->loadSectionByIdentifier($inputSectionIdentifier);
            $contentInfo = $contentService->loadContentInfo($inputContentId);

            $sectionService->assignSection($contentInfo, $section);
        }
        catch(\eZ\Publish\API\Repository\Exceptions\UnauthorizedException $e)
        {
            $io->error($e->getMessage());
        }

        $io->success('Assignment successful');
    }
}
