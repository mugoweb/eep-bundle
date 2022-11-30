<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion;
use eZ\Publish\API\Repository\Values\Content\Query\SortClause;
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

class EepLocationSubtreeCommand extends Command
{
    public function __construct
    (
        SearchService $searchService,
        LocationService $locationService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->searchService = $searchService;
        $this->locationService = $locationService;
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
            ->setName('eep:location:subtree')
            ->setAliases(array('eep:lo:subtree'))
            ->setDescription('Returns subtree information')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $location = $this->locationService->loadLocation($inputLocationId);

        $query = new LocationQuery();
        $query->query = new Criterion\Subtree($location->pathString);
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->sortClauses = array
        (
            new SortClause\Location\Path(),
        );
        $query->performCount = true;

        $result = $this->searchService->findLocations($query);
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
                'name',
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
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputLocationId]",
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
                    $this->locationService->getLocationChildCount($searchHit->valueObject),
                    $searchHit->valueObject->contentInfo->name,
                );
            }

            $query->offset += $query->limit;
            $result = $this->searchService->findLocations($query);
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
