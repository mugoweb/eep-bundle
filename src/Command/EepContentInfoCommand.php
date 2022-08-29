<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
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

class EepContentInfoCommand extends Command
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
            ->setName('eep:content:info')
            ->setAliases(array('eep:co:info'))
            ->setDescription('Returns content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);

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
            // ...
        );
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputContentId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $content->contentInfo->id),
            array('contentTypeId', $content->contentInfo->contentTypeId),
            array('name', $content->contentInfo->name),
            array('sectionId', $content->contentInfo->sectionId),
            array('currentVersionNo', $content->contentInfo->currentVersionNo),
            array('published', $content->contentInfo->published),
            array('ownerId', $content->contentInfo->ownerId),
            array('modificationDate', $content->contentInfo->modificationDate->format('c')),
            array('publishedDate', $content->contentInfo->publishedDate->format('c')),
            array('alwaysAvailable', (integer) $content->contentInfo->alwaysAvailable),
            array('remoteId', $content->contentInfo->remoteId),
            array('mainLanguageCode', $content->contentInfo->mainLanguageCode),
            array('mainLocationId', $content->contentInfo->mainLocationId),
            array('status', $content->contentInfo->status),
            new TableSeparator(),
            array('contentTypeIdentifier', $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId)->identifier),
            array('modificationDateTimestamp', $content->contentInfo->modificationDate->format('U')),
            array('publishedDateTimestamp', $content->contentInfo->publishedDate->format('U')),
        );

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();

        return Command::SUCCESS;
    }
}
