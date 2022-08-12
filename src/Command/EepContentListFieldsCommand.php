<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentListFieldsCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        PermissionResolver $permissionResolver,
        UserService $userService
    )
    {
        $this->contentService = $contentService;
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
            ->setName('eep:content:listfields')
            ->setAliases(array('eep:co:listfields'))
            ->setDescription('Returns content field list')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputUserId = $input->getOption('user-id');

	$this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $fields = $content->getFields();

        $headers = array
        (
           array
           (
               'id',
               'fieldDefIdentifier',
               'value',
               'languageCode',
               'fieldTypeIdentifier',
           )
        );
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()} [$inputContentId]",
                array('colspan' => count($headers[0]))
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($fields as $field)
        {
            $rows[] = array
            (
                $field->id,
                $field->fieldDefIdentifier,
                $field->value,
                $field->languageCode,
                $field->fieldTypeIdentifier,
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
