<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateListCommand extends Command
{
    public function __construct
    (
        private readonly ObjectStateService  $objectStateService,
        private readonly PermissionResolver $permissionResolver,
        private readonly UserService $userService,
        private readonly EepLogger $logger
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:objectstate:list')
            ->setAliases(array('eep:os:list'))
            ->setDescription('Returns object state list for a given object state group')
            ->addArgument('group-id', InputArgument::REQUIRED, 'Object state group id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $inputUserId = $input->getOption('user-id');
        $groupId = $input->getArgument('group-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $group = $this->objectStateService->loadObjectStateGroup($groupId);
        $states = $this->objectStateService->loadObjectStates($group);
        $resultCount = count($states);
        $resultSet = $resultCount;

        $headers = array
        (
            array
            (
                'id',
                'identifier',
                'priority',
                'defaultLanguageCode',
                'languageCodes *',
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
                "{$this->getName()} (group: {$group->identifier})",
                array('colspan' => ($colWidth == 1)? 1 : $colWidth-1)
            ),
            new TableCell
            (
                "Results: $resultSet",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($states as $state)
        {
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $state->id; }
            if(!in_array('identifier', $hideColumns)) { $row[] = $state->identifier; }
            if(!in_array('priority', $hideColumns)) { $row[] = $state->priority; }
            if(!in_array('defaultLanguageCode', $hideColumns)) { $row[] = $state->defaultLanguageCode; }
            if(!in_array('languageCodes', $hideColumns)) { $row[] = implode(',', $state->languageCodes); }
            if(!in_array('name', $hideColumns)) { $row[] = $state->getName(); }

            $rows[] = $row;
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();

        return Command::SUCCESS;
    }
}