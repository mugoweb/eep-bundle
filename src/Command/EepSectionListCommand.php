<?php

namespace MugoWeb\Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepSectionListCommand extends ContainerAwareCommand
{
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

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));

        $sections = $repository->getSectionService()->loadSections();
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
    }
}
