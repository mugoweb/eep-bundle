<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\URLAliasService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasLookupCommand extends Command
{
    public function __construct
    (
        URLAliasService $urlAliasService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->urlAliasService = $urlAliasService;
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
            ->setName('eep:urlalias:info')
            ->setAliases(array('eep:ua:info'))
            ->setDescription('Returns URL alias by URL')
            ->addArgument('url', InputArgument::REQUIRED, 'URL')
            ->addOption('language', 'l', InputOption::VALUE_OPTIONAL, 'Language code')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUrl = $input->getArgument('url');
        $inputLanguage = $input->getOption('language');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $urlAlias = $this->urlAliasService->lookup($inputUrl, $inputLanguage);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $colWidth = count($headers[0]);
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputUrl]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $urlAlias->id),
            array('type', $urlAlias->type),
            array('destination', $urlAlias->destination),
            array('path', $urlAlias->path),
            array('languageCodes', implode(',', $urlAlias->languageCodes)),
            array('alwaysAvailable', (integer) $urlAlias->alwaysAvailable),
            array('isHistory', (integer) $urlAlias->isHistory),
            array('isCustom', (integer) $urlAlias->isCustom),
            array('forward', (integer) $urlAlias->forward),
        );

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}