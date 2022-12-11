<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentRelatedCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        SectionService $sectionService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->sectionService = $sectionService;
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
            ->setName('eep:content:related')
            ->setAliases(array('eep:co:related'))
            ->setDescription('Returns related content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $related = $this->contentService->loadRelations($content->versionInfo);
        $relatedCount = count($related);

        $headers = array
        (
            array
            (
                'id',
                'mainLocationId',
                'sectionId',
                'sectionIdentifier *',
                'contentTypeId',
                'contentTypeIdentifier *',
                'sourceFieldIdentifier',
                'relationTypeId',
                'relationType *',
                'name',
            ),
        );

        $hideColumns = ($input->getOption('hide-columns'))? explode(',', $input->getOption('hide-columns')) : array();
        $headerKeys = array_map(array('MugoWeb\Eep\Bundle\Services\EepUtilities', 'stripColumnMarkers'), $headers[0]);
        foreach($hideColumns as $columnKey)
        {
            $searchResultKey = array_search($columnKey, $headerKeys);
            if($searchResultKey !== false)
            {
                unset($headers[0][$searchResultKey]);
            }
        }

        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("* = custom/composite/lookup value", array('colspan' => $colWidth)),
            // ...
        );
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId]",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
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
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $relation->destinationContentInfo->id; }
            if(!in_array('mainLocationId', $hideColumns)) { $row[] = $relation->destinationContentInfo->mainLocationId; }
            if(!in_array('sectionId', $hideColumns)) { $row[] = $relation->destinationContentInfo->sectionId; }
            if(!in_array('sectionIdentifier', $hideColumns)) { $row[] = $this->sectionService->loadSection($relation->destinationContentInfo->sectionId)->identifier; }
            if(!in_array('contentTypeId', $hideColumns)) { $row[] = $relation->destinationContentInfo->contentTypeId; }
            if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $this->contentTypeService->loadContentType($relation->destinationContentInfo->contentTypeId)->identifier; }
            if(!in_array('sourceFieldIdentifier', $hideColumns)) { $row[] = $relation->sourceFieldDefinitionIdentifier; }
            if(!in_array('relationTypeId', $hideColumns)) { $row[] = $relation->type; }
            if(!in_array('relationType', $hideColumns)) { $row[] = EepUtilities::getContentRelationTypeLabel($relation->type); }
            if(!in_array('name', $hideColumns)) { $row[] = $relation->destinationContentInfo->name; }

            $rows[] = $row;
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
