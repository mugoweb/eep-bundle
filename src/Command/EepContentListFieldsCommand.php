<?php

namespace MugoWeb\Eep\Bundle\Command;

use Eep\Bundle\Component\Console\Helper\Table;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentListFieldsCommand extends ContainerAwareCommand
{
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

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));

        $content = $repository->getContentService()->loadContent($inputContentId);
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
