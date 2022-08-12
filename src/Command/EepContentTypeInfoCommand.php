<?php

namespace MugoWeb\Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentTypeInfoCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:contenttype:info')
            ->setAliases(array('eep:ct:info'))
            ->setDescription('Returns content type information')
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
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $contentTypeService = $repository->getContentTypeService();

        $contentType = $contentTypeService->loadContentTypeByIdentifier($inputContentTypeIdentifier);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
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

        $rows = array
        (
            array( 'id', $contentType->id ),
            array( 'status', $contentType->status ),
            array( 'identifier', $contentType->identifier ),
            array( 'creationDate', $contentType->creationDate->format('c') ),
            array( 'creationDateTimestamp', $contentType->creationDate->format('U') ),
            array( 'modificationDate', $contentType->modificationDate->format('c') ),
            array( 'modificationDateTimestamp', $contentType->modificationDate->format('U') ),
            array( 'urlAliasSchema', $contentType->urlAliasSchema ),
            array( 'nameSchema', $contentType->nameSchema ),
            array( 'isContainer', (integer) $contentType->isContainer ),
            array( 'creatorId', $contentType->creatorId ),
            array( 'modifierId', $contentType->modifierId ),
            array( 'remoteId', $contentType->remoteId ),
        );

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}
