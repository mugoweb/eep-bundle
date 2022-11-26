<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use MugoWeb\Eep\Bundle\Services\EepUtilities;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentVersionInfoCommand extends Command
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
            ->setName('eep:content:versioninfo')
            ->setAliases(array('eep:co:versioninfo'))
            ->setDescription('Returns content version information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('version-number', InputArgument::REQUIRED, 'Version number')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputVersionNumber = $input->getArgument('version-number');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $versionInfo = $this->contentService->loadVersionInfoById($inputContentId, $inputVersionNumber);

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
            array('id', $versionInfo->id),
            array('versionNo', $versionInfo->versionNo),
            array('modificationDate', $versionInfo->modificationDate->format('c')),
            array('creatorId', $versionInfo->creatorId),
            array('creationDate', $versionInfo->creationDate->format('c')),
            array('status', $versionInfo->status),
            array('initialLanguageCode', $versionInfo->initialLanguageCode),
            array('languageCodes', implode(',', $versionInfo->languageCodes)),
            new TableSeparator(),
            array('initialLanguageName', $versionInfo->names[$versionInfo->initialLanguageCode]),
            array('creationDateTimestamp', $versionInfo->creationDate->format('U')),
            array('modificationDateTimestamp', $versionInfo->modificationDate->format('U')),
            array('statusLabel', EepUtilities::getContentVersionStatusLabel($versionInfo->status)),
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
