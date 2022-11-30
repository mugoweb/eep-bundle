<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentTypeListCommand extends Command
{
    public function __construct
    (
        ContentTypeService $contentTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
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
            ->setName('eep:contenttype:list')
            ->setAliases(array('eep:ct:list'))
            ->setDescription('Returns content type list')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentTypeGroups = $this->contentTypeService->loadContentTypeGroups();
        $contentTypes = array();
        foreach ($contentTypeGroups as $contentTypeGroup)
        {
            $contentTypes = array_merge($contentTypes, $this->contentTypeService->loadContentTypes($contentTypeGroup));
        }
        usort($contentTypes, function($a, $b) { return strcmp($a->identifier, $b->identifier); });
        $contentTypesCount = count($contentTypes);

        $headers = array
        (
            array
            (
                'id',
                'identifier',
                'isContainer',
                'remoteId',
                'name',
            ),
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()}",
                array('colspan' => count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: " . (($contentTypesCount)? 1 : 0) . " - $contentTypesCount / $contentTypesCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($contentTypes as $contentType)
        {
            $rows[] = array
            (
                $contentType->id,
                $contentType->identifier,
                (integer) $contentType->isContainer,
                $contentType->remoteId,
                $contentType->names[$contentType->mainLanguageCode],
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
