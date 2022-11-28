<?php

namespace MugoWeb\Eep\Bundle\Command;

use MugoWeb\Eep\Bundle\Services\EepLogger;
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
        UserService $userService,
        EepLogger $logger
    )
    {
        $this->contentService = $contentService;
        $this->fieldTypeService = $fieldTypeService;
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
            ->setName('eep:contentfield:tostring')
            ->setAliases(array('eep:cf:tostring'))
            ->setDescription('Returns content field value as JSON string')
            ->addArgument('content-id', InputArgument::REQUIRED, 'Content id')
            ->addArgument('content-field-identifier', InputArgument::REQUIRED, 'Content field identifier')
            ->addOption('user-id', 'u', InputOption::VALUE_OPTIONAL, 'User id for content operations', 14)
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

        switch ($field->fieldTypeIdentifier)
        {
            case 'ezboolean':
            {
                $fieldValueString = (boolean) $field->value ? 1 : 0;
            }
            break;

            case 'ezobjectrelation':
            {
                $fieldValueString = (integer) $field->value->destinationContentId;
            }
            break;

            case 'ezobjectrelationlist':
            {
                $fieldValueString = $field->value->destinationContentIds;
            }
            break;

            default:
            {
                $fieldValueString = (string) $field->value;
            }
        }
        $fieldValueString = json_encode($fieldValueString);

        $io = new SymfonyStyle($input, $output);
        if ($input->getOption('no-newline'))
        {
            $io->write($fieldValueString);
        }
        else
        {
            $io->writeln($fieldValueString);
        }

        return Command::SUCCESS;
    }
}
