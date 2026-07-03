<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use Ibexa\Contracts\Core\Repository\ObjectStateService;
use Ibexa\Contracts\Core\Repository\PermissionResolver;
use Ibexa\Contracts\Core\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepObjectStateInfoCommand extends Command
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
            ->setName('eep:objectstate:info')
            ->setAliases(array('eep:os:info'))
            ->setDescription('Returns object state information')
            ->addArgument('state-id', InputArgument::REQUIRED, 'Object state id')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('with-group', 'w', InputOption::VALUE_NONE, 'Display object states group this state belongs to')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputArgumentStateId = $input->getArgument('state-id');
        $inputUserId = $input->getOption('user-id');
        $inputWithGroup = $input->getOption('with-group');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $state = $this->objectStateService->loadObjectState($inputArgumentStateId);

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
        if ($inputWithGroup){ $legendHeaders[] = new TableCell("# object state group shown with custom 'group' key prefix", array('colspan' => $colWidth)); }
        $legendHeaders = array_reverse($legendHeaders);
        foreach ($legendHeaders as $row)
        {
            array_unshift($headers, array($row));
        }
        $infoHeader = array
        (
            new TableCell("{$this->getName()} [$inputArgumentStateId]", array('colspan' => $colWidth))
        );
        array_unshift($headers, $infoHeader);

        $rows = array
        (
            array('id', $state->id),
            array('identifier', $state->identifier),
            array('mainLanguageCode', $state->mainLanguageCode),
            array('languageCodes', implode(',', $state->languageCodes)),
            new TableSeparator(),
            array('name', $state->getName()),
            array('description', $state->getDescription()),
        );
        if ($inputWithGroup)
        {
            $group = $state->getObjectStateGroup();

            $rows[] = new TableSeparator();
            $rows[] = new TableSeparator();
            $rows[] = array('groupId', $group->id);
            $rows[] = array('groupIdentifier', $group->identifier);
            $rows[] = array('groupDefaultLanguageCode', $group->defaultLanguageCode);
            $rows[] = array('groupmainLanguageCode', $group->mainLanguageCode);
            $rows[] = array('grouplanguageCodes', implode(',', $group->languageCodes));
            $rows[] = array('groupName', $group->getName());
            $rows[] = array('groupDescription', $group->getDescription());
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