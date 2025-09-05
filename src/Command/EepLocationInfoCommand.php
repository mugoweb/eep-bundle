<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\LocationService;
use Ibexa\Contracts\Core\Repository\ContentService;
use Ibexa\Contracts\Core\Repository\ContentTypeService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Ibexa\Contracts\Core\Repository\URLAliasService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepLocationInfoCommand extends Command
{
    public function __construct
    (
        private readonly LocationService $locationService,
        private readonly ContentService $contentService,
        private readonly ContentTypeService $contentTypeService,
        private readonly PermissionResolver $permissionResolver,
        private readonly UserService $userService,
        private readonly URLAliasService $urlAliasService
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:location:info')
            ->setAliases(array('eep:lo:info'))
            ->setDescription('Returns location information')
            ->addArgument('location-id', InputArgument::REQUIRED, 'Location id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('with-content-info', 'w', InputOption::VALUE_NONE, 'Display location\'s content info')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputLocationId = $input->getArgument('location-id');
        $inputUserId = $input->getOption('user-id');
        $inputWithContentInfo = $input->getOption('with-content-info');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $location = $this->locationService->loadLocation($inputLocationId);

        if ($inputWithContentInfo)
        {
            $content = $this->contentService->loadContent($location->getContentInfo()->id);
        }

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
            new TableCell("# 2nd data section(s) shows custom/composite/lookup values", array('colspan' => $colWidth)),
            new TableCell("# contentInfo values shown with custom 'content' key prefix", array('colspan' => $colWidth)),
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
                "{$this->getName()} [{$location->id}]",
                array('colspan' => $colWidth)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            // location details
            array('id', $location->id),
            array('status', $location->status),
            array('priority', $location->priority),
            array('hidden', (integer) $location->hidden),
            array('invisible', (integer) $location->invisible),
            array('remoteId', $location->remoteId),
            array('parentLocationId', $location->parentLocationId),
            array('pathString', $location->pathString),
            array('depth', $location->depth),
            array('sortField', $location->sortField),
            array('sortOrder', $location->sortOrder),
            new TableSeparator(),
            array('childCount', $this->locationService->getLocationChildCount($location)),
            array('subtreeSize', $this->locationService->getSubtreeSize($location)),
            array('urlAlias', $this->urlAliasService->reverseLookup($location)->path),
        );
        if ($inputWithContentInfo)
        {
            $rows = array_merge
            (
                $rows,
                array
                (
                    new TableSeparator(),
                    new TableSeparator(),
                    // location contentInfo details
                    array('contentId', $location->getContentInfo()->id),
                    array('contentTypeId', $location->getContentInfo()->contentTypeId),
                    array('contentName', $location->getContentInfo()->name),
                    array('contentSectionId', $location->getContentInfo()->sectionId),
                    array('contentCurrentVersionNo', $location->getContentInfo()->currentVersionNo),
                    array('contentPublished', $location->getContentInfo()->published),
                    array('contentOwnerId', $location->getContentInfo()->ownerId),
                    array('contentModificationDate', $location->getContentInfo()->modificationDate->format('c')),
                    array('contentPublishedDate', $location->getContentInfo()->publishedDate->format('c')),
                    array('contentAlwaysAvailable', $location->getContentInfo()->alwaysAvailable),
                    array('contentRemoteId', $location->getContentInfo()->remoteId),
                    array('contentMainLanguageCode', $location->getContentInfo()->mainLanguageCode),
                    array('contentMainLocationId', $location->getContentInfo()->mainLocationId),
                    array('contentStatus', $location->getContentInfo()->status),
                    new TableSeparator(),
                    array('contentTypeIdentifier', $this->contentTypeService->loadContentType($location->getContentInfo()->contentTypeId)->identifier),
                    array('contentModificationDateTimestamp', $location->getContentInfo()->modificationDate->format('U')),
                    array('contentPublishedDateTimestamp', $location->getContentInfo()->publishedDate->format('U')),
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
