<?php

namespace MugoWeb\Eep\Bundle\Command;

use eZ\Publish\API\Repository\ContentService;
use eZ\Publish\API\Repository\FieldTypeService;
use eZ\Publish\API\Repository\PermissionResolver;
use eZ\Publish\API\Repository\UserService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class EepContentFieldToStringCommand extends Command
{
    public function __construct
    (
        ContentService $contentService,
        FieldTypeService $fieldTypeService,
        PermissionResolver $permissionResolver,
        UserService $userService
    )
    {
        $this->contentService = $contentService;
        $this->fieldTypeService = $fieldTypeService;
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

	$this->permissionResolver->setCurrentUserReference($this->userService->loadUser($inputUserId));

        $content = $this->contentService->loadContent($inputContentId);
        $field = $content->getField($inputContentFieldIdentifier);
        $fieldType = $this->fieldTypeService->getFieldType($field->fieldTypeIdentifier);
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
