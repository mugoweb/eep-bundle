<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\ObjectStateService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateListContentCommand extends Command
{
    public function __construct
    (
        ContentTypeService $contentTypeService,
        ObjectStateService  $objectStateService,
        PermissionResolver $permissionResolver,
        SearchService $searchService,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentTypeService = $contentTypeService;
        $this->objectStateService = $objectStateService;
        $this->permissionResolver = $permissionResolver;
        $this->searchService = $searchService;
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
            ->setName('eep:objectstate:listcontent')
            ->setAliases(array('eep:os:listcontent'))
            ->setDescription('Returns content list for a given object state')
            ->addArgument('state-id', InputArgument::REQUIRED, 'Object state id')
            ->addArgument('group-id', InputArgument::OPTIONAL, 'Object state group id', null)
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        $inputArgumentStateId = $input->getArgument('state-id');
        $inputArgumentGroupId = $input->getArgument('group-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $state = $this->objectStateService->loadObjectState($inputArgumentStateId);
        $group = ($inputArgumentGroupId)? $this->objectStateService->loadObjectStateGroup($inputArgumentGroupId) : $state->getObjectStateGroup();

        $query = new Query();
        $query->filter = new Criterion\ObjectStateId($state->id);
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->performCount = true;

        try
        {
            $searchResult = $this->searchService->findContent($query);
        }
        catch (\Exception $e)
        {
            $io->error($e->getMessage());
            $this->logger->error($this->getName() . " error", array($e->getMessage()));
        }
        $query->performCount = false;

        $resultCount = count($searchResult->searchHits);
        $resultOffset = ($resultCount)? ($query->offset + 1) : 0;
        $resultLimit = ($resultCount)? ($query->offset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'id',
                'contentTypeIdentifier *',
                'mainLocationId',
                'name',
            ),
        );

        $hideColumns = ($input->getOption('hide-columns'))? explode(',', $input->getOption('hide-columns')) : array();
        $headerKeys = array_map(array('MugoWeb\Eep\Bundle\Services\EepUtilities', 'stripColumnMarkers'), $headers[0]);
        foreach($hideColumns as $columnKey)
        {
            $resultKey = array_search($columnKey, $headerKeys);
            if($resultKey !== false)
            {
                unset($headers[0][$resultKey]);
            }
        }

        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("* = custom/composite/lookup value", array('colspan' => $colWidth)),
        );
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $groupIdentifier = ($group)? ", group: {$group->identifier}" : "";
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} (state: {$state->identifier}{$groupIdentifier})",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $searchResult->totalCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $contentTypeIdentifiers = array();

        $rows = array();
        while($query->offset < $resultLimit)
        {
            foreach ($searchResult->searchHits as $hit)
            {
                $content = $hit->valueObject;
                $contentInfo = $content->contentInfo;

                if(!isset($contentTypeIdentifiers[$contentInfo->contentTypeId]))
                {
                    $contentTypeIdentifiers[$contentInfo->contentTypeId] = $this->contentTypeService->loadContentType($contentInfo->contentTypeId)->identifier;
                }

                $row = array();
                if(!in_array('id', $hideColumns)) { $row[] = $contentInfo->id; }
                if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $contentTypeIdentifiers[$contentInfo->contentTypeId]; }
                if(!in_array('mainLocationId', $hideColumns)) { $row[] = $contentInfo->mainLocationId; }
                if(!in_array('name', $hideColumns)) { $row[] = $contentInfo->name; }

                $rows[] = $row;
            }

            $query->offset += $query->limit;
            $searchResult = $this->searchService->findContent($query);
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
