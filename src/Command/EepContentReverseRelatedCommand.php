<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentReverseRelatedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:reverserelated')
            ->setAliases(array('eep:co:reverserelated'))
            ->setDescription('Returns reverse related content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();

        $content = $contentService->loadContent($inputContentId);
        $reverseRelated = $contentService->loadReverseRelations($content->contentInfo);
        $reverseRelatedCount = count($reverseRelated);

        $headers = array
        (
            array
            (
                'id',
                'mainLocationId',
                'sectionId',
                'contentTypeIdentifier',
                'sourceFieldIdentifier',
                'relationType',
                'name',
            ),
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId]",
                array('colspan' => count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: " . (($reverseRelatedCount)? 1 : 0) . " - $reverseRelatedCount / $reverseRelatedCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($reverseRelated as $relation)
        {
            $rows[] = array
            (
                $relation->sourceContentInfo->id,
                $relation->sourceContentInfo->mainLocationId,
                $relation->sourceContentInfo->sectionId,
                $contentTypeService->loadContentType($relation->sourceContentInfo->contentTypeId)->identifier,
                $relation->sourceFieldDefinitionIdentifier,
                $relation->type,
                $relation->sourceContentInfo->name,
            );
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
