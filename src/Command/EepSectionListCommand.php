<?php

namespace MugoWeb\Eep\Bundle\Command;

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
    public function __construct(SectionService $sectionService, PermissionResolver $permissionResolver, UserService $userService)
    {
        $this->sectionService = $sectionService;
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
            ->setName('eep:section:list')
            ->setAliases(array('eep:se:list'))
            ->setDescription('Returns section list')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
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
        $infoHeader = array
        (
            new TableCell
            (
                "{$this->getName()}",
                array('colspan' => count($headers[0])-1)
            ),
            new TableCell
            (
                "Results: 1 - $sectionsCount / $sectionsCount",
                array('colspan' => 1)
            )
        );
        array_unshift($headers, $infoHeader);

        $rows = array();
        foreach ($sections as $section)
        {
            $rows[] = array
            (
                $section->id,
                $section->identifier,
                $section->name,
            );
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
