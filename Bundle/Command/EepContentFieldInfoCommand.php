<?php

namespace Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentFieldInfoCommand extends ContainerAwareCommand
{
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

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
        $contentTypeService = $repository->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
        $field = $contentType->fieldDefinitionsByIdentifier[$inputContentFieldIdentifier];

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
    }
}
