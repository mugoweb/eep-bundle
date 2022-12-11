<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Query\Solr\Criterion as EepSolrCriterion;
use Ibexa\Contracts\Core\Repository\Values\Content\LocationQuery;
use Ibexa\Contracts\Core\Repository\Values\Content\Query\Criterion;
use Ibexa\Contracts\Core\Repository\SearchService;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class EepSearchSearchCommand extends Command
{
    public function __construct
    (
        SearchService $searchService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger,
        ParameterBagInterface $params
    )
    {
        $this->searchService = $searchService;
        $this->locationService = $locationService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;
        $this->logger = $logger;
        $this->params = $params;

        parent::__construct();
    }

    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:search:search')
            ->setAliases(array('eep:sr:search'))
            ->setDescription('Returns search result information')
            ->addArgument('query', InputArgument::REQUIRED, 'Search query')
            ->addArgument('filter', InputArgument::OPTIONAL, 'Search filter', '')
            ->addOption('raw-query', null, InputOption::VALUE_NONE, 'Apply query as raw Solr syntax string')
            ->addOption('raw-filter', null, InputOption::VALUE_NONE, 'Apply filter as raw Solr syntax string')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', 25)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);

        if ($this->params->get('search_engine') !== 'solr' && ($input->getOption('raw-query') || $input->getOption('raw-filter')))
        {
            $io->error('The --raw-* options require the configured search_engine to be solr. Configured search_engine is '. $this->params->get('search_engine'));
            return Command::FAILURE;
        }

        $inputQuery = $input->getArgument('query');
        $inputFilter = $input->getArgument('filter');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $query = new LocationQuery();
        $query->query = new Criterion\FullText($inputQuery);
        if ($input->getOption('raw-query'))
        {
            $query->query = new EepSolrCriterion\Raw($inputQuery);
        }
        $query->filter = ($input->getArgument('filter'))? new Criterion\FullText($input->getArgument('filter')) : '';
        if ($input->getOption('raw-filter'))
        {
            $query->filter = ($input->getArgument('filter'))? new EepSolrCriterion\Raw($input->getArgument('filter')) : '';
        }
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->performCount = true;

        try
        {
            $result = $this->searchService->findLocations($query);
        }
        catch(Exception $e)
        {
            $io->error($e->getMessage());
            $this->logger->error($this->getName() . " error", array($e->getMessage()));
        }
        $query->performCount = false;

        $resultCount = count($result->searchHits);
        $resultOffset = ($resultCount)? ($query->offset + 1) : 0;
        $resultLimit = ($resultCount)? ($query->offset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'locationId',
                'contentId',
                'contentTypeId',
                'contentTypeIdentifier *',
                'pathString',
                'priority',
                'hidden',
                'invisible',
                'children *',
                'score',
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
        $inputFilter = ($inputFilter)? $inputFilter : " $inputFilter";
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputQuery]",
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
        while($query->offset < $resultLimit)
        {
            foreach ($result->searchHits as $searchHit)
            {
                $row = array();
                if(!in_array('locationId', $hideColumns)) { $row[] = $searchHit->valueObject->id; }
                if(!in_array('contentId', $hideColumns)) { $row[] = $searchHit->valueObject->contentInfo->id; }
                if(!in_array('contentTypeId', $hideColumns)) { $row[] = $searchHit->valueObject->contentInfo->contentTypeId; }
                if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $this->contentTypeService->loadContentType($searchHit->valueObject->contentInfo->contentTypeId)->identifier; }
                if(!in_array('pathString', $hideColumns)) { $row[] = $searchHit->valueObject->pathString; }
                if(!in_array('priority', $hideColumns)) { $row[] = $searchHit->valueObject->priority; }
                if(!in_array('hidden', $hideColumns)) { $row[] = (integer) $searchHit->valueObject->hidden; }
                if(!in_array('invisible', $hideColumns)) { $row[] = (integer) $searchHit->valueObject->invisible; }
                if(!in_array('children', $hideColumns)) { $row[] = $this->locationService->getLocationChildCount($searchHit->valueObject); }
                if(!in_array('score', $hideColumns)) { $row[] = $searchHit->score; }
                if(!in_array('name', $hideColumns)) { $row[] = $searchHit->valueObject->contentInfo->name; }

                $rows[] = $row;
            }

            $query->offset += $query->limit;
            $result = $this->searchService->findLocations($query);
        }

        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();

        return Command::SUCCESS;
    }
}
