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

class EepContentTypeListFieldsCommand extends ContainerAwareCommand
{
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
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentTypeIdentifier = $input->getArgument('content-type-identifier');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->setCurrentUser($repository->getUserService()->loadUser($inputUserId));
        $contentTypeService = $repository->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);
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
