<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
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
        UserService $userService
    )
    {
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
            ->setName('eep:contenttype:listfields')
            ->setAliases(array('eep:ct:listfields'))
            ->setDescription('Returns content type field list')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
        $fieldDefinitions = $contentType->fieldDefinitionsByIdentifier;

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
            $rows[] = array
            (
                $fieldIdentifier,
                $fieldDefinition->mainLanguageCode,
                $fieldDefinition->id,
                $fieldDefinition->fieldTypeIdentifier,
                (integer) $fieldDefinition->isSearchable,
                (integer) $fieldDefinition->isRequired,
                (integer) $fieldDefinition->isTranslatable,
                (integer) $fieldDefinition->isInfoCollector,
                $fieldDefinition->position,
                $fieldDefinition->fieldGroup,
                $fieldDefinition->names[$fieldDefinition->mainLanguageCode],
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
