<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\SectionService;
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

class EepContentReverseRelatedCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        SectionService $sectionService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
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
            ->setName('eep:content:reverserelated')
            ->setAliases(array('eep:co:reverserelated'))
            ->setDescription('Returns reverse related content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', 20)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');
        $inputOffset = $input->getOption('offset');
        $inputLimit = $input->getOption('limit');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);

        $reverseRelationsCount = $this->contentService->countReverseRelations($content->contentInfo);
        $inputLimit = (!$reverseRelationsCount || $inputLimit >= $reverseRelationsCount)? $reverseRelationsCount : $inputLimit;
        $reverseRelationList = $this->contentService->loadReverseRelationList($content->contentInfo, $inputOffset, $inputLimit);

        $resultCount = count($reverseRelationList->items);
        $resultOffset = ($resultCount)? ($inputOffset + 1) : 0;
        $resultLimit = ($resultCount)? ($inputOffset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'id',
                'mainLocationId',
                'sectionId',
                'sectionIdentifier *',
                'contentTypeId',
                'contentTypeIdentifier *',
                'sourceFieldIdentifier',
                'relationTypeId',
                'relationType *',
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
                "{$this->getName()} [$inputContentId]",
                array('colspan' => $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $reverseRelationsCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($reverseRelationList->items as $relationListItem)
        {
            $relation = $relationListItem->getRelation();
            $rows[] = array
            (
                $relation->sourceContentInfo->id,
                $relation->sourceContentInfo->mainLocationId,
                $relation->sourceContentInfo->sectionId,
                $this->sectionService->loadSection($relation->destinationContentInfo->sectionId)->identifier,
                $relation->sourceContentInfo->contentTypeId,
                $this->contentTypeService->loadContentType($relation->sourceContentInfo->contentTypeId)->identifier,
                $relation->sourceFieldDefinitionIdentifier,
                $relation->type,
                EepUtilities::getContentRelationTypeLabel($relation->type),
                $relation->sourceContentInfo->name,
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
