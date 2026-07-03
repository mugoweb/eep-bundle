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
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateGroupInfoCommand extends Command
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
            ->setName('eep:objectstate:groupinfo')
            ->setAliases(array('eep:os:groupinfo'))
            ->setDescription('Returns object state group information')
            ->addArgument('group-id', InputArgument::REQUIRED, 'Object state group id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('with-states', 'w', InputOption::VALUE_NONE, 'Display object states belonging to this group')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputArgumentGroupId = $input->getArgument('group-id');
        $inputUserId = $input->getOption('user-id');
        $inputWithStates = $input->getOption('with-states');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $group = $this->objectStateService->loadObjectStateGroup($inputArgumentGroupId);

        $headers = array
        (
            array
            (
                'key',
                'value',
            ),
        );
        $colWidth = count($headers[0]);
        $legendHeaders = array
        (
            new TableCell("# 2nd data section shows custom/composite/lookup values", array('colspan' => $colWidth)),
        );
        if ($inputWithStates){ $legendHeaders[] = new TableCell("# object states shown with custom 'state' key prefix", array('colspan' => $colWidth)); }
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputArgumentGroupId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $group->id),
            array('identifier', $group->identifier),
            array('defaultLanguageCode', $group->defaultLanguageCode),
            array('mainLanguageCode', $group->mainLanguageCode),
            array('languageCodes', implode(',', $group->languageCodes)),
            new TableSeparator(),
            array('name', $group->getName()),
            array('description', $group->getDescription()),
        );
        if ($inputWithStates)
        {
            $states = $this->objectStateService->loadObjectStates($group);

            $rows[] = new TableSeparator();
            $rows[] = new TableSeparator();
            $rows[] = array('stateCount', count($states));

            foreach ($states as $state)
            {
                $rows[] = new TableSeparator();
                $rows[] = array('stateId', $state->id);
                $rows[] = array('stateIdentifier', $state->identifier);
                $rows[] = array('statePriority', $state->priority);
                $rows[] = array('stateDefaultLanguageCode', $state->defaultLanguageCode);
                $rows[] = array('stateName', $state->getName());
            }
        }

        $io = new SymfonyStyle($input, $output);
        $table = new Table($output);
        $table->setHeaders($headers);
        $table->setRows($rows);
        $table->render();
        $io->newLine();
    }
}