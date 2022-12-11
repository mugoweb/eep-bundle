<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
use MugoWeb\Eep\Bundle\Component\Console\Helper\Table;
use eZ\Publish\API\Repository\SectionService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepSectionListCommand extends Command
{
    public function __construct
    (
        SectionService $sectionService,
        PermissionResolver $permissionResolver,
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->sectionService = $sectionService;
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
            ->setName('eep:section:list')
            ->setAliases(array('eep:se:list'))
            ->setDescription('Returns section list')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('hide-columns', null, InputOption::VALUE_OPTIONAL, 'CSV of column(s) to hide from results table')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputUserId = $input->getOption('user-id');

        $this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $sections = $this->sectionService->loadSections();
        $sectionsCount = count($sections);

        $headers = array
        (
            array
            (
                'id',
                'identifier',
                'name',
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

        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()}",
                array('colspan' => (count($headers[0]) == 1)? 1 : count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: " . (($sectionsCount)? 1 : 0) . " - $sectionsCount / $sectionsCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($sections as $section)
        {
            $row = array();
            if(!in_array('id', $hideColumns)) { $row[] = $section->id; }
            if(!in_array('identifier', $hideColumns)) { $row[] = $section->identifier; }
            if(!in_array('name', $hideColumns)) { $row[] = $section->name; }

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
