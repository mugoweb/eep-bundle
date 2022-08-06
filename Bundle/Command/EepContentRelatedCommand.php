<?php

namespace Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentRelatedCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:related')
            ->setAliases(array('eep:co:related'))
            ->setDescription('Returns related content information')
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

        if ($inputContentId)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
            $contentTypeService = $repository->getContentTypeService();
            $contentService = $repository->getContentService();

            $content = $contentService->loadContent($inputContentId);
            $related = $contentService->loadRelations($content->versionInfo);
            $relatedCount = count($related);

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
                    "Results: " . (($relatedCount)? 1 : 0) . " - $relatedCount / $relatedCount",
                    array('colspan' => 1)
                )
            );
            array_unshift($headers, $infoHeader);

            $rows = array();
            foreach ($related as $relation)
            {
                $rows[] = array
                (
                    $relation->destinationContentInfo->id,
                    $relation->destinationContentInfo->mainLocationId,
                    $relation->destinationContentInfo->sectionId,
                    $contentTypeService->loadContentType($relation->destinationContentInfo->contentTypeId)->identifier,
                    $relation->sourceFieldDefinitionIdentifier,
                    $relation->type,
                    $relation->destinationContentInfo->name,
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
}
