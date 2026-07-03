<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\ObjectStateService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateGroupListCommand extends Command
{
    public function __construct
    (
        ObjectStateService  $objectStateService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->objectStateService = $objectStateService;
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
            ->setName('eep:objectstate:grouplist')
            ->setAliases(array('eep:os:grouplist'))
            ->setDescription('Returns object state group list')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('offset', null, InputOption::VALUE_OPTIONAL, 'Offset', 0)
            ->addOption('limit', null, InputOption::VALUE_OPTIONAL, 'Limit', -1)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUserId = $input->getOption('user-id');
        $inputOffset = $input->getOption('offset');
        $inputLimit = $input->getOption('limit');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $groups = $this->objectStateService->loadObjectStateGroups($inputOffset, $inputLimit);
        $resultCount = count($groups);
        $resultOffset = ($resultCount)? ($inputOffset + 1) : 0;
        $resultLimit = ($resultCount)? ($inputOffset + $resultCount) : 0;
        $resultSet = ($resultOffset == $resultLimit)? $resultLimit : $resultOffset . " - " . $resultLimit;

        $headers = array
        (
            array
            (
                'id',
                'identifier',
                'defaultLanguageCode',
                'languageCodes *',
                'stateCount *',
                'name *',
            ),
        );

        $hideColumns = ($input->getOption('hide-columns'))? explode(',', $input->getOption('hide-columns')) : array();
        $headerKeys = array_map(array('MugoWeb\Eep\Bundle\Services\EepUtilities', 'stripColumnMarkers'), $headers[0]);
        foreach($hideColumns as $columnKey)
        {
            $resultKey = array_search($columnKey, $headerKeys);
            if($resultKey !== false)
            {
                unset($headers[0][$resultKey]);
            }
        }

        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("* = custom/composite/lookup value", array('colspan' => $colWidth)),
            // ...
        );
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
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

        $rows = array();
        foreach ($groups as $group)
        {
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $group->id; }
            if(!in_array('identifier', $hideColumns)) { $row[] = $group->identifier; }
            if(!in_array('defaultLanguageCode', $hideColumns)) { $row[] = $group->defaultLanguageCode; }
            if(!in_array('languageCodes', $hideColumns)) { $row[] = implode(',', $group->languageCodes); }
            if(!in_array('stateCount', $hideColumns)) { $row[] = count($this->objectStateService->loadObjectStates($group)); }
            if(!in_array('name', $hideColumns)) { $row[] = $group->getName(); }

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