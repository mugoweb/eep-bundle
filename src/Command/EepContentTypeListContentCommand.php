<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion\ContentTypeIdentifier;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentTypeListContentCommand extends Command
{
    public function __construct
    (
        SearchService $searchService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->searchService = $searchService;
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
            ->setName('eep:contenttype:listcontent')
            ->setAliases(array('eep:ct:listcontent'))
            ->setDescription('Returns content information by content type identifier')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $query = new LocationQuery();
        $query->filter = new ContentTypeIdentifier($inputContentTypeIdentifier);
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->performCount = true;

        $result = $this->searchService->findContentInfo($query);
        $query->performCount = false;

        $resultCount = count($result->searchHits);
        $resultOffset = ($resultCount)? ($query->offset + 1) : 0;
        $resultLimit = ($resultCount)? ($query->offset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'contentId',
                'mainLocationId',
                'sectionId',
                'currentVersionNo',
                'remoteId',
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

        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentTypeIdentifier]",
                array('colspan' => (count($headers[0]) == 1)? 1 : count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $result->totalCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        while($query->offset < $resultLimit)
        {
            foreach ($result->searchHits as $searchHit)
            {
                $row = array();
                if(!in_array('contentId', $hideColumns)) { $row[] = $searchHit->valueObject->id; }
                if(!in_array('mainLocationId', $hideColumns)) { $row[] = $searchHit->valueObject->mainLocationId; }
                if(!in_array('sectionId', $hideColumns)) { $row[] = $searchHit->valueObject->sectionId; }
                if(!in_array('currentVersionNo', $hideColumns)) { $row[] = $searchHit->valueObject->currentVersionNo; }
                if(!in_array('remoteId', $hideColumns)) { $row[] = $searchHit->valueObject->remoteId; }
                if(!in_array('name', $hideColumns)) { $row[] = $searchHit->valueObject->name; }

                $rows[] = $row;
            }

            $query->offset += $query->limit;
            $result = $this->searchService->findContentInfo($query);
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
