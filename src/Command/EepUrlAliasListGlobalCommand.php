<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use eZ\Publish\API\Repository\URLAliasService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepUrlAliasListGlobalCommand extends Command
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
            ->setName('eep:urlalias:listglobal')
            ->setAliases(array('eep:ua:listglobal'))
            ->setDescription('Lists global URL aliases')
            ->addOption('language', 'l', InputOption::VALUE_OPTIONAL, 'Language code')
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', -1)
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for URL alias operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputLanguage = $input->getOption('language');
        $inputOffset = (integer) $input->getOption('offset');
        $inputLimit = (integer) $input->getOption('limit');
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $urlAliases = $this->urlAliasService->listGlobalAliases
        (
            $inputLanguage,
            $inputOffset,
            $inputLimit
        );

        $headers = array
        (
            array
            (
                'id',
                'type',
                'destination',
                'path',
                'languageCodes',
                'alwaysAvailable',
                'isHistory',
                'isCustom',
                'forward',
            ),
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

        $rows = array();
        foreach ($urlAliases as $urlAlias)
        {
            $row = array();

            if(!in_array('id', $hideColumns)) { $row[] = $urlAlias->id; }
            if(!in_array('type', $hideColumns)) { $row[] = $urlAlias->type; }
            if(!in_array('destination', $hideColumns)) { $row[] = $urlAlias->destination; }
            if(!in_array('path', $hideColumns)) { $row[] = $urlAlias->path; }
            if(!in_array('languageCodes', $hideColumns)) { $row[] = implode(',', $urlAlias->languageCodes); }
            if(!in_array('alwaysAvailable', $hideColumns)) { $row[] = (integer) $urlAlias->alwaysAvailable; }
            if(!in_array('isHistory', $hideColumns)) { $row[] = (integer) $urlAlias->isHistory; }
            if(!in_array('isCustom', $hideColumns)) { $row[] = (integer) $urlAlias->isCustom; }
            if(!in_array('forward', $hideColumns)) { $row[] = (integer) $urlAlias->forward; }

            $rows[] = $row;
        }

        $resultCount = count($rows);
        $resultSet = ($resultCount)? ($inputOffset + 1) . " - " . ($inputOffset + $resultCount) : 0;
        $colWidth = count($headers[0]);

        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()}",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet / $resultCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}