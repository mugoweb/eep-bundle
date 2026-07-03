<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ObjectStateService;
use eZ\Publish\API\Repository\ContentService;
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

class EepObjectStateGetContentCommand extends Command
{
    public function __construct
    (
        ObjectStateService $objectStateService,
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->objectStateService = $objectStateService;
        $this->contentService = $contentService;
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
            ->setName('eep:objectstate:getcontent')
            ->setAliases(array('eep:os:getcontent'))
            ->setDescription('Returns object state assigned to content, for a given object state group')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('group-id', InputArgument::REQUIRED, 'Object state group id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputGroupId = $input->getArgument('group-id');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentInfo = $this->contentService->loadContentInfo($inputContentId);
        $objectStateGroup = $this->objectStateService->loadObjectStateGroup($inputGroupId);
        $objectState = $this->objectStateService->getContentState($contentInfo, $objectStateGroup);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $colWidth = count($headers[0]);
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputContentId, $inputGroupId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('contentId', $contentInfo->id),
            array('contentName', $contentInfo->name),
            new TableSeparator(),
            array('groupId', $objectStateGroup->id),
            array('groupIdentifier', $objectStateGroup->identifier),
            new TableSeparator(),
            array('stateId', $objectState->id),
            array('stateIdentifier', $objectState->identifier),
        );

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}