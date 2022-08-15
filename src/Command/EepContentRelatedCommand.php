<?php

namespace MugoWeb\Eep\Bundle\Command;

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

class EepContentRelatedCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService
    )
    {
        $this->contentService = $contentService;
        $this->contentTypeService = $contentTypeService;
        $this->permissionResolver = $permissionResolver;
        $this->userService = $userService;

        parent::__construct();
    }

    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:content:related')
            ->setAliases(array('eep:co:related'))
            ->setDescription('Returns related content information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset')
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

	    $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $related = $this->contentService->loadRelations($content->versionInfo);
        $relatedCount = count($related);

        $headers = array
        (
            array
            (
                'id',
                'mainLocationId',
                'sectionId',
                'contentTypeIdentifier',
                'sourceFieldIdentifier',
                'relationType',
                'name',
            ),
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId]",
                array('colspan' => count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: " . (($relatedCount)? 1 : 0) . " - $relatedCount / $relatedCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($related as $relation)
        {
            $rows[] = array
            (
                $relation->destinationContentInfo->id,
                $relation->destinationContentInfo->mainLocationId,
                $relation->destinationContentInfo->sectionId,
                $this->contentTypeService->loadContentType($relation->destinationContentInfo->contentTypeId)->identifier,
                $relation->sourceFieldDefinitionIdentifier,
                $relation->type,
                $relation->destinationContentInfo->name,
            );
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
