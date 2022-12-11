<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
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

class EepContentTypeListFieldsCommand extends Command
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
            ->setName('eep:contenttype:listfields')
            ->setAliases(array('eep:ct:listfields'))
            ->setDescription('Returns content type field list')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
        $fieldDefinitions = $contentType->getFieldDefinitions();

        $headers = array
        (
           array
           (
               'identifier',
               'mainLanguageCode',
               'id',
               'fieldTypeIdentifier',
               'isSearchable',
               'isRequired',
               'isTranslatable',
               'isInfoCollector',
               'position',
               'fieldGroup',
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

        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentTypeIdentifier]",
                array('colspan' => count($headers[0]))
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($fieldDefinitions as $fieldIdentifier => $fieldDefinition)
        {
            $row = array();
            if(!in_array('identifier', $hideColumns)) { $row[] = $fieldDefinition->identifier; }
            if(!in_array('mainLanguageCode', $hideColumns)) { $row[] = $fieldDefinition->mainLanguageCode; }
            if(!in_array('id', $hideColumns)) { $row[] = $fieldDefinition->id; }
            if(!in_array('fieldTypeIdentifier', $hideColumns)) { $row[] = $fieldDefinition->fieldTypeIdentifier; }
            if(!in_array('isSearchable', $hideColumns)) { $row[] = (integer) $fieldDefinition->isSearchable; }
            if(!in_array('isRequired', $hideColumns)) { $row[] = (integer) $fieldDefinition->isRequired; }
            if(!in_array('isTranslatable', $hideColumns)) { $row[] = (integer) $fieldDefinition->isTranslatable; }
            if(!in_array('isInfoCollector', $hideColumns)) { $row[] = (integer) $fieldDefinition->isInfoCollector; }
            if(!in_array('position', $hideColumns)) { $row[] = $fieldDefinition->position; }
            if(!in_array('fieldGroup', $hideColumns)) { $row[] = $fieldDefinition->fieldGroup; }
            if(!in_array('name', $hideColumns)) { $row[] = $fieldDefinition->names[$fieldDefinition->mainLanguageCode]; }

            $rows[] = $row;
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
