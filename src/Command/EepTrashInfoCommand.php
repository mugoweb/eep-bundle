<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use eZ\Publish\API\Repository\TrashService;
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

class EepTrashInfoCommand extends Command
{
    public function __construct
    (
        TrashService $trashService,
        PermissionResolver $permissionResolver,
        ContentTypeService $contentTypeService,
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
            ->setName('eep:trash:info')
            ->setAliases(array('eep:tr:info'))
            ->setDescription('Returns trash item information')
            ->addArgument('trash-item-id', InputArgument::REQUIRED, 'Trash item id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('with-content-info', 'w', InputOption::VALUE_NONE, 'Display trash item\'s content info')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputArgumentTrashItemId = $input->getArgument('trash-item-id');
        $inputUserId = $input->getOption('user-id');
        $inputWithContentInfo = $input->getOption('with-content-info');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $trashItem = $this->trashService->loadTrashItem($inputArgumentTrashItemId);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("# 2nd data section shows custom/composite/lookup values", array('colspan' => $colWidth)),
        );
        if ($inputWithContentInfo){ $legendHeaders[] = new TableCell("# contentInfo values shown with custom 'content' key prefix", array('colspan' => $colWidth)); }
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputArgumentTrashItemId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $trashItem->id),
            array('status', $trashItem->status),
            array('priority', $trashItem->priority),
            array('hidden', (integer) $trashItem->hidden),
            array('invisible', (integer) $trashItem->invisible),
            array('remoteId', $trashItem->remoteId),
            array('parentLocationId', $trashItem->parentLocationId),
            array('pathString', $trashItem->pathString),
            array('depth', $trashItem->depth),
            array('sortField', $trashItem->sortField),
            array('sortOrder', $trashItem->sortOrder),
            new TableSeparator(),
            array('ownerId', $trashItem->contentInfo->ownerId),
            array('ownerName', $trashItem->contentInfo->getOwner()->getName()),
            array('sortFieldLabel', EepUtilities::getLocationSortFieldLabel($trashItem->sortField)),
            array('sortOrderLabel', EepUtilities::getLocationSortOrderLabel($trashItem->sortOrder)),
        );
        if ($inputWithContentInfo)
        {
            $contentInfo = $trashItem->getContentInfo();
            $rows = array_merge
            (
                $rows,
                array
                (
                    new TableSeparator(),
                    new TableSeparator(),
                    // location contentInfo details
                    array('contentId', $contentInfo->id),
                    array('contentTypeId', $contentInfo->contentTypeId),
                    array('contentName', $contentInfo->name),
                    array('contentSectionId', $contentInfo->sectionId),
                    array('contentCurrentVersionNo', $contentInfo->currentVersionNo),
                    array('contentPublished', (integer) $contentInfo->published),
                    array('contentOwnerId', $contentInfo->ownerId),
                    array('contentModificationDate', $contentInfo->modificationDate->format('c')),
                    array('contentPublishedDate', $contentInfo->publishedDate->format('c')),
                    array('contentAlwaysAvailable', (integer) $contentInfo->alwaysAvailable),
                    array('contentRemoteId', $contentInfo->remoteId),
                    array('contentMainLanguageCode', $contentInfo->mainLanguageCode),
                    array('contentMainLocationId', $contentInfo->mainLocationId),
                    array('contentStatus', $contentInfo->status),
                    new TableSeparator(),
                    array('contentTypeIdentifier', $this->contentTypeService->loadContentType($contentInfo->contentTypeId)->identifier),
                    array('contentModificationDateTimestamp', $contentInfo->modificationDate->format('U')),
                    array('contentPublishedDateTimestamp', $contentInfo->publishedDate->format('U')),
                )
            );
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
