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

class EepContentFieldInfoCommand extends Command
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
            ->setName('eep:contentfield:info')
            ->setAliases(array('eep:cf:info'))
            ->setDescription('Returns content field information')
            ->addArgument('content-type-identifier', InputArgument::REQUIRED, 'Content type identifier')
            ->addArgument('content-field-identifier', InputArgument::REQUIRED, 'Content field identifier')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputContentFieldIdentifier = $input->getArgument('content-field-identifier');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $contentType = $this->contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
        $field = $contentType->getFieldDefinitions()->get($inputContentFieldIdentifier);

        $headers = array
        (
            array
            (
                'key',
                'value',
            )
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentTypeIdentifier,$inputContentFieldIdentifier]",
                array('colspan' => count($headers[0]))
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $field->id),
            array('identifier', $field->identifier),
            array('fieldGroup', $field->fieldGroup),
            array('position', $field->position),
            array('fieldTypeIdentifier', $field->fieldTypeIdentifier),
            array('isTranslatable', (integer) $field->isTranslatable),
            array('isRequired', (integer) $field->isRequired),
            array('isInfoCollector', (integer) $field->isInfoCollector),
            array('isSearchable', (integer) $field->isSearchable),
            array('mainLanguageCode', $field->mainLanguageCode),
            array('name', $field->names[$field->mainLanguageCode]),
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
