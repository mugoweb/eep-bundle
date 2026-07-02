<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\Values\Content\Query;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
use eZ\Publish\API\Repository\TrashService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepTrashListCommand extends Command
{
    public function __construct
    (
        TrashService  $trashService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->trashService = $trashService;
        $this->contentTypeService = $contentTypeService;
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
            ->setName('eep:trash:list')
            ->setAliases(array('eep:tr:list'))
            ->setDescription('Returns trashed content list')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $query = new Query();
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->sortClauses = array
        (
            new SortClause\Trash\DateTrashed(Query::SORT_DESC)
        );
        $result = $this->trashService->findTrashItems($query);
        $resultCount = count($result->items);
        $resultOffset = ($resultCount)? ($query->offset + 1) : 0;
        $resultLimit = ($resultCount)? ($query->offset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'id',
                'contentId',
                'contentTypeId',
                'contentTypeIdentifier *',
                'parentLocationId',
                'ownerId',
                'ownerName *',
                'pathString',
                'depth',
                'trashed',
                'trashedDate *',
                'remoteId',
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
                "{$this->getName()}",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $result->totalCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($result->items as $trashItem)
        {
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $trashItem->id; }
            if(!in_array('contentId', $hideColumns)) { $row[] = $trashItem->contentInfo->id; }
            if(!in_array('contentTypeId', $hideColumns)) { $row[] = $trashItem->contentInfo->contentTypeId; }
            if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $this->contentTypeService->loadContentType($trashItem->contentInfo->contentTypeId)->identifier; }
            if(!in_array('parentLocationId', $hideColumns)) { $row[] = $trashItem->parentLocationId; }
            if(!in_array('ownerId', $hideColumns)) { $row[] = $trashItem->contentInfo->ownerId; }
            if(!in_array('ownerName', $hideColumns)) { $row[] = $this->userService->loadUser($trashItem->contentInfo->ownerId)->getName(); }
            if(!in_array('pathString', $hideColumns)) { $row[] = $trashItem->pathString; }
            if(!in_array('depth', $hideColumns)) { $row[] = $trashItem->depth; }
            if(!in_array('trashed', $hideColumns)) { $row[] = $trashItem->trashed->format('U'); }
            if(!in_array('trashedDate', $hideColumns)) { $row[] = $trashItem->trashed->format('c'); }
            if(!in_array('remoteId', $hideColumns)) { $row[] = $trashItem->remoteId; }
            if(!in_array('name', $hideColumns)) { $row[] = $trashItem->contentInfo->name; }

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
