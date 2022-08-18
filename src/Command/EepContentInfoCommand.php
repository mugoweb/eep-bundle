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
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputContentId]", array('colspan' => count($headers[0])))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $content->contentInfo->id),
            array('contentTypeId', $content->contentInfo->contentTypeId),
            array('contentTypeIdentifier', $this->contentTypeService->loadContentType($content->contentInfo->contentTypeId)->identifier),
            array('name', $content->contentInfo->name),
            array('sectionId', $content->contentInfo->sectionId),
            array('currentVersionNo', $content->contentInfo->currentVersionNo),
            array('published', $content->contentInfo->published),
            array('ownerId', $content->contentInfo->ownerId),
            array('modificationDate', $content->contentInfo->modificationDate->format('c')),
            array('modificationDateTimestamp', $content->contentInfo->modificationDate->format('U')),
            array('publishedDate', $content->contentInfo->publishedDate->format('c')),
            array('publishedDateTimestamp', $content->contentInfo->publishedDate->format('U')),
            array('alwaysAvailable', $content->contentInfo->alwaysAvailable),
            array('remoteId', $content->contentInfo->remoteId),
            array('mainLanguageCode', $content->contentInfo->mainLanguageCode),
            array('mainLocationId', $content->contentInfo->mainLocationId),
            array('status', $content->contentInfo->status),
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
