<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use eZ\Publish\API\Repository\Values\Content\Query\Criterion\SectionId;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\Values\Content\LocationQuery;
use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SectionService;
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

class EepSectionListContentCommand extends Command
{
    public function __construct
    (
        SearchService $searchService,
        ContentTypeService $contentTypeService,
        SectionService $sectionService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->searchService = $searchService;
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
            ->setName('eep:section:listcontent')
            ->setAliases(array('eep:se:listcontent'))
            ->setDescription('Returns content list by section identifier')
            ->addArgument('section-identifier', InputArgument::REQUIRED, 'Section identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputSectionIdentifier = $input->getArgument('section-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $section = $this->sectionService->loadSectionByIdentifier($inputSectionIdentifier);

        $query = new LocationQuery();
        $query->filter = new SectionId($section->id);
        $query->offset = ($input->getOption('offset'))? (integer) $input->getOption('offset') : $query->offset;
        $query->limit = ($input->getOption('limit'))? (integer) $input->getOption('limit') : $query->limit;
        $query->performCount = true;

        $result = $this->searchService->findContentInfo($query);
        $resultLimit = ($input->getOption('limit'))? ($query->offset + $query->limit) : $result->totalCount;
        $query->performCount = false;

        $headers = array
        (
            array
            (
                'contentId',
                'contentTypeId',
                'contentTypeIdentifier *',
                'mainLocationId',
                'sectionId',
                'ownerId',
                'currentVersionNo',
                'remoteId',
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
                "{$this->getName()} [$inputSectionIdentifier]",
                array('colspan' => $colWidth-1)
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
                    $searchHit->valueObject->contentTypeId,
                    $this->contentTypeService->loadContentType($searchHit->valueObject->contentTypeId)->identifier,
                    $searchHit->valueObject->mainLocationId,
                    $searchHit->valueObject->sectionId,
                    $searchHit->valueObject->ownerId,
                    $searchHit->valueObject->currentVersionNo,
                    $searchHit->valueObject->remoteId,
                    $searchHit->valueObject->name,
                );
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
