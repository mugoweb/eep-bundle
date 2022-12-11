<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentDraftListCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
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
            ->setName('eep:content:draftlist')
            ->setAliases(array('eep:co:draftlist'))
            ->setDescription('Returns content draft list')
            ->addArgument('draft-user-id', InputArgument::REQUIRED, 'User id of the draft owner.')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', 25)
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputDraftUser = ($input->getArgument('draft-user-id'))? $this->userService->loadUser($input->getArgument('draft-user-id')) : $input->getArgument('draft-user-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $draftList = $this->contentService->loadContentDraftList($inputDraftUser, $input->getOption('offset'), $input->getOption('limit'));

        $resultCount = count($draftList->items);
        $resultOffset = ($resultCount)? ($input->getOption('offset') + 1) : 0;
        $resultLimit = ($resultCount)? ($input->getOption('offset') + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'id',
                'versionNo',
                'modificationDateTimestamp *',
                'creationDateTimestamp *',
                'languageCodes',
                'contentId',
                'contentTypeId',
                'contentTypeIdentifier *',
                'name',
            )
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
                "{$this->getName()} [{$input->getArgument('draft-user-id')}]",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / {$draftList->totalCount}",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach($draftList->items as $draftListItem)
        {
            if($draftListItem->hasVersionInfo())
            {
                $versionInfo = $draftListItem->getVersionInfo();
                $contentInfo = $versionInfo->getContentInfo();
                $row = array();
                if(!in_array('id', $hideColumns)) { $row[] = $versionInfo->id; }
                if(!in_array('versionNo', $hideColumns)) { $row[] = $versionInfo->versionNo; }
                if(!in_array('modificationDateTimestamp', $hideColumns)) { $row[] = $versionInfo->modificationDate->format('U'); }
                if(!in_array('creationDateTimestamp', $hideColumns)) { $row[] = $versionInfo->creationDate->format('U'); }
                if(!in_array('languageCodes', $hideColumns)) { $row[] = implode(',', $versionInfo->languageCodes); }
                if(!in_array('contentId', $hideColumns)) { $row[] = $contentInfo->id; }
                if(!in_array('contentTypeId', $hideColumns)) { $row[] = $contentInfo->contentTypeId; }
                if(!in_array('contentTypeIdentifier', $hideColumns)) { $row[] = $this->contentTypeService->loadContentType($contentInfo->contentTypeId)->identifier; }
                if(!in_array('name', $hideColumns)) { $row[] = $versionInfo->getName(); }

                $rows[] = $row;
            }
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
