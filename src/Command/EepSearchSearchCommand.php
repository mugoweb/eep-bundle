<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Query\Solr\Criterion as EepSolrCriterion;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\LocationService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
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
                'name',
                'children *',
                'score',
            ),
        );
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
                array('colspan' => $colWidth-1)
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
                $rows[] = array
                (
                    $searchHit->valueObject->id,
                    $searchHit->valueObject->contentInfo->id,
                    $searchHit->valueObject->contentInfo->contentTypeId,
                    $this->contentTypeService->loadContentType($searchHit->valueObject->contentInfo->contentTypeId)->identifier,
                    $searchHit->valueObject->pathString,
                    $searchHit->valueObject->priority,
                    (integer) $searchHit->valueObject->hidden,
                    (integer) $searchHit->valueObject->invisible,
                    $searchHit->valueObject->contentInfo->name,
                    $this->locationService->getLocationChildCount($searchHit->valueObject),
                    $searchHit->score,
                );
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
