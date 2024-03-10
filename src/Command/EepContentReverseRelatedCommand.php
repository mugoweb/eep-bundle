<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\SectionService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentReverseRelatedCommand extends Command
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
            ->setName('eep:content:reverserelated')
            ->setAliases(array('eep:co:reverserelated'))
            ->setDescription('Returns reverse related content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', 20)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');
        $inputOffset = $input->getOption('offset');
        $inputLimit = $input->getOption('limit');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);

        $reverseRelationsCount = $this->contentService->countReverseRelations($content->contentInfo);
        $inputLimit = (!$reverseRelationsCount || $inputLimit >= $reverseRelationsCount)? $reverseRelationsCount : $inputLimit;
        $reverseRelationList = $this->contentService->loadReverseRelationList($content->contentInfo, $inputOffset, $inputLimit);

        $resultCount = count($reverseRelationList->items);
        $resultOffset = ($resultCount)? ($inputOffset + 1) : 0;
        $resultLimit = ($resultCount)? ($inputOffset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

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
                array('colspan' => $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $reverseRelationsCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($reverseRelationList->items as $relationListItem)
        {
            $relation = $relationListItem->getRelation();
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $relation->sourceContentInfo->id; }
            if(!in_array('mainLocationId', $hideColumns)) { $row[] = $relation->sourceContentInfo->mainLocationId; }
            if(!in_array('sectionId', $hideColumns)) { $row[] = $relation->sourceContentInfo->sectionId; }
            if(!in_array('sectionIdentifier', $hideColumns)) { $row[] = $this->sectionService->loadSection($relation->destinationContentInfo->sectionId)->identifier; }
            if(!in_array('contentTypeId', $hideColumns)) { $row[] = $relation->sourceContentInfo->contentTypeId; }
            if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $this->contentTypeService->loadContentType($relation->sourceContentInfo->contentTypeId)->identifier; }
            if(!in_array('sourceFieldIdentifier', $hideColumns)) { $row[] = $relation->sourceFieldDefinitionIdentifier; }
            if(!in_array('relationTypeId', $hideColumns)) { $row[] = $relation->type; }
            if(!in_array('relationType', $hideColumns)) { $row[] = EepUtilities::getContentRelationTypeLabel($relation->type); }
            if(!in_array('name', $hideColumns)) { $row[] = $relation->sourceContentInfo->name; }

            $rows[] = $row;
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();

        return Command::SUCCESS;
    }
}
