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

class EepContentTypeInfoCommand extends Command
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
            ->setName('eep:contenttype:info')
            ->setAliases(array('eep:ct:info'))
            ->setDescription('Returns content type information')
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
