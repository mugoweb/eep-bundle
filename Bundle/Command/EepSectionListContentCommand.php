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

class EepSectionListContentCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:section:listcontent')
            ->setAliases(array('eep:se:listcontent'))
            ->setDescription('Returns content list by section identifier')
            ->addArgument('section-identifier', InputArgument::REQUIRED, 'Section identifier')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSectionIdentifier = $input->getArgument('section-identifier');
        $inputUserId = $input->getOption('user-id');

        if ($inputSectionIdentifier)
        {
            $repository = $this->getContainer()->get('ezpublish.api.repository');
            $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
            $searchService = $repository->getSearchService();
            $contentTypeService = $repository->getContentTypeService();

            $section = $repository->getSectionService()->loadSectionByIdentifier($inputSectionIdentifier);

            $query = new LocationQuery();
            $query->filter = new SectionId($section->id);
            $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
            $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
            $query->performCount = true;

            $result = $searchService->findContentInfo($query);
            $resultLimit = ($input->getOption('limit'))? ($query->offset + $query->limit) : $result->totalCount;
            $query->performCount = false;

            $headers = array
            (
                array
                (
                    'contentId',
                    'mainLocationId',
                    'sectionId',
                    'ownerId',
                    'currentVersionNo',
                    'remoteId',
                    'contentTypeIdentifier',
                    'name',
                ),
            );
            $infoHeader = array
            (
                new TableCell
                (
                    "{$this->getName()} [$inputSectionIdentifier]",
                    array('colspan' => count($headers[0])-1)
                ),
                new TableCell
                (
                    "Results: " . ($query->offset + 1) . " - {$resultLimit} / {$result->totalCount}",
                    array('colspan' => 1)
                )
            );
            array_unshift($headers, $infoHeader);

            $rows = array();
            while($query->offset < $resultLimit)
            {
                foreach ($result->searchHits as $searchHit)
                {
                    $rows[] = array
                    (
                        $searchHit->valueObject->id,
                        $searchHit->valueObject->mainLocationId,
                        $searchHit->valueObject->sectionId,
                        $searchHit->valueObject->ownerId,
                        $searchHit->valueObject->currentVersionNo,
                        $searchHit->valueObject->remoteId,
                        $contentTypeService->loadContentType($searchHit->valueObject->contentTypeId)->identifier,
                        $searchHit->valueObject->name,
                    );
                }

                $query->offset += $query->limit;
                $result = $searchService->findContentInfo($query);
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
