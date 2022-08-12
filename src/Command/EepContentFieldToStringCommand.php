<?php

namespace MugoWeb\Eep\Bundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentFieldToStringCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $help = <<<EOD
TODO

EOD;

        $this
            ->setName('eep:contentfield:tostring')
            ->setAliases(array('eep:cf:tostring'))
            ->setDescription('(experimental!) Returns string representation of content field information')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('content-field-identifier', InputArgument::REQUIRED, 'Content field identifier')
            ->addOption('user-id', 'uid', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
            ->addOption('separator', 's', InputOption::VALUE_OPTIONAL, 'Separator character', '|')
            ->addOption('no-newline', 'x', null, 'Output without newline')
            ->setHelp($help)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $inputContentId = $input->getArgument('content-id');
        $inputContentFieldIdentifier = $input->getArgument('content-field-identifier');
        $inputUserId = $input->getOption('user-id');

        $repository = $this->getContainer()->get('ezpublish.api.repository');
        $repository->getPermissionResolver()->setCurrentUserReference($repository->getUserService()->loadUser($inputUserId));
        $contentService = $repository->getContentService();
        $fieldTypeService = $repository->getFieldTypeService();

        $content = $contentService->loadContent($inputContentId);
        $field = $content->getField($inputContentFieldIdentifier);
        $fieldType = $fieldTypeService->getFieldType($field->fieldTypeIdentifier);
        $fieldValueHash = $fieldType->toHash($field->value);
        $fieldValueString = implode($input->getOption('separator'), (array) $fieldValueHash);

        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('no-newline'))
        {
            $io->write($fieldValueString);
        }
        else
        {
            $io->writeln($fieldValueString);
        }
    }
}
